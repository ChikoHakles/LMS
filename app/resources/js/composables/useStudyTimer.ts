import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

export type StudyTimerContext = {
    assignmentId: number;
    startUrl: string;
    heartbeatUrl: string;
    stopUrl: string;
    csrfToken: string;
};

type SessionPayload = { sessionId: string; seconds: number; dailySeconds: number; lastSequence: number };
type SavedSession = { sessionId: string; assignmentId: number; lastSequence: number };

const STORAGE_KEY = 'ruang-active-study-session';
const HEARTBEAT_INTERVAL_MS = 15_000;
const IDLE_TIMEOUT_MS = 60_000;

export function useStudyTimer(context: StudyTimerContext) {
    const dailySeconds = ref(0);
    const sessionSeconds = ref(0);
    const state = ref<'starting' | 'active' | 'paused' | 'unavailable'>('starting');
    const message = ref('');
    const activeMinutes = computed(() => Math.floor(dailySeconds.value / 60));
    let sessionId: string | null = null;
    let lastSequence = 0;
    let lastInteractionAt = 0;
    let lastHeartbeatAt = 0;
    let timer: number | undefined;
    let heartbeatInFlight = false;
    let startPromise: Promise<void> | null = null;
    let stopPromise: Promise<void> | null = null;

    function heartbeatUrl(id: string): string {
        return context.heartbeatUrl.replace('__SESSION_ID__', encodeURIComponent(id));
    }

    function stopUrl(id: string): string {
        return context.stopUrl.replace('__SESSION_ID__', encodeURIComponent(id));
    }

    function readSavedSession(): SavedSession | null {
        try {
            const raw = window.sessionStorage.getItem(STORAGE_KEY);
            if (!raw) return null;
            const value = JSON.parse(raw) as Partial<SavedSession>;
            return typeof value.sessionId === 'string' && typeof value.assignmentId === 'number' && typeof value.lastSequence === 'number'
                ? (value as SavedSession)
                : null;
        } catch {
            return null;
        }
    }

    function saveSession(): void {
        if (!sessionId) return;
        try {
            window.sessionStorage.setItem(
                STORAGE_KEY,
                JSON.stringify({
                    sessionId,
                    assignmentId: context.assignmentId,
                    lastSequence,
                } satisfies SavedSession),
            );
        } catch {
            // Server-side session recovery still works if browser storage is unavailable.
        }
    }

    function clearSavedSession(id: string): void {
        const saved = readSavedSession();
        if (saved?.sessionId !== id) return;
        try {
            window.sessionStorage.removeItem(STORAGE_KEY);
        } catch {
            // Ignore storage restrictions; an ended session is safe to reuse idempotently.
        }
    }

    async function postJson(url: string, body: Record<string, number | string>): Promise<SessionPayload> {
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': context.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        });
        if (!response.ok) {
            throw new Error(
                response.status === 409 ? 'Sesi belajar lain masih aktif di tab lain.' : 'Waktu belajar belum tersinkron. Coba lagi sebentar.',
            );
        }
        return (await response.json()) as SessionPayload;
    }

    function applyServerPayload(payload: SessionPayload): void {
        sessionId = payload.sessionId;
        sessionSeconds.value = payload.seconds;
        dailySeconds.value = payload.dailySeconds;
        lastSequence = payload.lastSequence;
        saveSession();
    }

    async function closeSavedSession(saved: SavedSession): Promise<void> {
        try {
            await postJson(stopUrl(saved.sessionId), { sequence: saved.lastSequence + 1 });
            clearSavedSession(saved.sessionId);
        } catch {
            // Starting the current assignment will return a clear conflict if the old stop did not reach the server.
        }
    }

    async function startSession(): Promise<void> {
        if (sessionId || startPromise) return startPromise ?? Promise.resolve();
        startPromise = (async () => {
            state.value = 'starting';
            message.value = '';
            await stopPromise;
            const saved = readSavedSession();
            if (saved && saved.assignmentId !== context.assignmentId) await closeSavedSession(saved);
            const payload = await postJson(context.startUrl, { assignment_id: context.assignmentId });
            applyServerPayload(payload);
            lastHeartbeatAt = Date.now();
            state.value = 'active';
            if (document.visibilityState !== 'visible') await stopSession();
        })()
            .catch((error: unknown) => {
                state.value = 'unavailable';
                message.value = error instanceof Error ? error.message : 'Waktu belajar belum tersinkron.';
            })
            .finally(() => {
                startPromise = null;
            });
        return startPromise;
    }

    async function heartbeat(): Promise<void> {
        if (!sessionId || heartbeatInFlight || state.value !== 'active') return;
        heartbeatInFlight = true;
        const sequence = lastSequence + 1;
        const id = sessionId;
        try {
            const payload = await postJson(heartbeatUrl(id), { sequence });
            applyServerPayload(payload);
            lastHeartbeatAt = Date.now();
            message.value = '';
        } catch (error) {
            // Keep the same sequence until an acknowledgement; retrying it is safe after a lost response.
            message.value = error instanceof Error ? error.message : 'Waktu belajar belum tersinkron.';
            if (error instanceof Error && error.message.startsWith('Sesi belajar lain')) {
                sessionId = null;
                state.value = 'paused';
            }
        } finally {
            heartbeatInFlight = false;
        }
    }

    async function stopSession(): Promise<void> {
        if (stopPromise) return stopPromise;
        const id = sessionId;
        if (!id) {
            state.value = 'paused';
            return;
        }
        state.value = 'paused';
        stopPromise = (async () => {
            try {
                const payload = await postJson(stopUrl(id), { sequence: lastSequence + 1 });
                applyServerPayload(payload);
                sessionId = null;
                clearSavedSession(id);
            } catch (error) {
                // A future start is idempotent and reconciles whether this stop reached the server.
                sessionId = null;
                message.value = error instanceof Error ? error.message : 'Sesi belajar akan dipulihkan saat tersambung.';
            }
        })().finally(() => {
            stopPromise = null;
        });
        return stopPromise;
    }

    function sendLeaveBeacon(): void {
        if (!sessionId || !navigator.sendBeacon) return;
        const data = new FormData();
        data.append('_token', context.csrfToken);
        data.append('sequence', String(lastSequence + 1));
        navigator.sendBeacon(stopUrl(sessionId), data);
    }

    function recordInteraction(): void {
        lastInteractionAt = Date.now();
        if (document.visibilityState === 'visible' && state.value !== 'active') void startSession();
    }

    function onVisibilityChange(): void {
        if (document.visibilityState === 'hidden') {
            void stopSession();
        }
    }

    function tick(): void {
        if (document.visibilityState !== 'visible' || state.value !== 'active') return;
        if (Date.now() - lastInteractionAt >= IDLE_TIMEOUT_MS) {
            void stopSession();
            return;
        }
        if (Date.now() - lastHeartbeatAt >= HEARTBEAT_INTERVAL_MS) void heartbeat();
    }

    onMounted(() => {
        lastInteractionAt = Date.now();
        if (document.visibilityState === 'visible') void startSession();
        else state.value = 'paused';
        window.addEventListener('pointerdown', recordInteraction, { passive: true });
        window.addEventListener('pointermove', recordInteraction, { passive: true });
        window.addEventListener('keydown', recordInteraction, { passive: true });
        window.addEventListener('scroll', recordInteraction, { passive: true });
        window.addEventListener('touchstart', recordInteraction, { passive: true });
        document.addEventListener('visibilitychange', onVisibilityChange);
        window.addEventListener('pagehide', sendLeaveBeacon);
        timer = window.setInterval(tick, 5_000);
    });

    onBeforeUnmount(() => {
        if (timer !== undefined) window.clearInterval(timer);
        window.removeEventListener('pointerdown', recordInteraction);
        window.removeEventListener('pointermove', recordInteraction);
        window.removeEventListener('keydown', recordInteraction);
        window.removeEventListener('scroll', recordInteraction);
        window.removeEventListener('touchstart', recordInteraction);
        document.removeEventListener('visibilitychange', onVisibilityChange);
        window.removeEventListener('pagehide', sendLeaveBeacon);
        sendLeaveBeacon();
    });

    return { activeMinutes, dailySeconds, sessionSeconds, state, message };
}
