<script setup lang="ts">
import UserAccountActions from '@/components/admin/UserAccountActions.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    users: {
        data: Array<{
            id: number;
            name: string;
            email: string;
            role: string;
            status: string;
            created_at: string;
        }>;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        current_page: number;
        last_page: number;
        total: number;
    };
}>();

const roleLabels: Record<string, string> = {
    admin: 'Administrator',
    tutor: 'Tutor',
    student: 'Siswa',
};
</script>

<template>
    <section class="overflow-hidden rounded-xl border border-border bg-card shadow-sm" aria-labelledby="users-heading">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border px-5 py-4 sm:px-6">
            <div>
                <h2 id="users-heading" class="text-lg font-semibold text-card-foreground">Daftar akun</h2>
                <p class="mt-1 text-sm text-muted-foreground">{{ users.total }} akun terdaftar</p>
            </div>
        </div>

        <div v-if="users.data.length" class="overflow-x-auto">
            <table class="w-full min-w-[760px] border-collapse text-left text-sm">
                <thead class="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-medium sm:px-6">Pengguna</th>
                        <th scope="col" class="px-5 py-3 font-medium">Peran</th>
                        <th scope="col" class="px-5 py-3 font-medium">Status</th>
                        <th scope="col" class="px-5 py-3 font-medium">Dibuat</th>
                        <th scope="col" class="px-5 py-3 font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="user in users.data" :key="user.id" class="align-top">
                        <td class="px-5 py-4 sm:px-6">
                            <p class="font-medium text-card-foreground">{{ user.name }}</p>
                            <p class="mt-1 text-muted-foreground">{{ user.email }}</p>
                        </td>
                        <td class="px-5 py-4 text-card-foreground">{{ roleLabels[user.role] ?? user.role }}</td>
                        <td class="px-5 py-4">
                            <span
                                :class="
                                    user.status === 'active'
                                        ? 'border-success/30 bg-success-soft text-success'
                                        : 'border-border bg-muted text-muted-foreground'
                                "
                                class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium"
                            >
                                {{ user.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-5 py-4 text-muted-foreground">{{ new Date(user.created_at).toLocaleDateString('id-ID') }}</td>
                        <td class="px-5 py-4"><UserAccountActions :user="user" /></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="px-5 py-12 text-center sm:px-6">
            <p class="font-medium text-card-foreground">Belum ada akun</p>
            <p class="mt-1 text-sm text-muted-foreground">Akun baru akan muncul setelah dibuat.</p>
        </div>

        <nav
            v-if="users.last_page > 1"
            class="flex flex-wrap items-center justify-between gap-3 border-t border-border px-5 py-4 sm:px-6"
            aria-label="Halaman daftar akun"
        >
            <p class="text-sm text-muted-foreground">Halaman {{ users.current_page }} dari {{ users.last_page }}</p>
            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="link in users.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    :aria-current="link.active ? 'page' : undefined"
                    :aria-disabled="!link.url"
                    :class="[
                        link.active
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border bg-background text-foreground hover:bg-muted',
                        !link.url ? 'pointer-events-none opacity-50' : '',
                    ]"
                    class="rounded-md border px-3 py-1.5 text-sm"
                    v-html="link.label"
                />
            </div>
        </nav>
    </section>
</template>
