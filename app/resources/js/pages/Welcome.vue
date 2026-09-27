<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import RuangBadge from '@/components/ruang/RuangBadge.vue';
import RuangCard from '@/components/ruang/RuangCard.vue';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BookOpenCheck, CalendarCheck, GraduationCap } from 'lucide-vue-next';
</script>

<template>
    <Head title="Ruang — ruang belajar" />

    <div class="min-h-svh bg-background text-foreground">
        <header class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <Link :href="route('home')" aria-label="Beranda Ruang">
                <AppLogo tone="surface" />
            </Link>
            <nav aria-label="Navigasi akun" class="flex items-center gap-2">
                <Link :href="route('login')" class="rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background">
                    Masuk
                </Link>
                <Button as-child>
                    <Link :href="route('register')">Buat akun</Link>
                </Button>
            </nav>
        </header>

        <main class="mx-auto grid w-full max-w-7xl gap-10 px-4 pb-12 pt-10 sm:px-6 sm:pb-16 sm:pt-16 lg:grid-cols-[minmax(0,1.1fr)_minmax(20rem,0.9fr)] lg:items-center lg:px-8 lg:py-20">
            <section class="space-y-6">
                <RuangBadge variant="primary">Ruang belajar</RuangBadge>
                <div class="space-y-4">
                    <h1 class="max-w-3xl text-4xl font-bold leading-tight tracking-tight sm:text-5xl lg:text-6xl">Belajar satu langkah setiap hari.</h1>
                    <p class="max-w-2xl text-lg leading-8 text-muted-foreground">Ruang menyatukan materi, aktivitas harian, dan pendampingan belajar dalam satu tempat yang hangat dan terarah.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <Button as-child size="lg">
                        <Link :href="route('register')">
                            Mulai belajar
                            <ArrowRight class="size-4" aria-hidden="true" />
                        </Link>
                    </Button>
                    <Link :href="route('login')" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-accent-foreground transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background">
                        Saya sudah punya akun
                    </Link>
                </div>
            </section>

            <section aria-label="Cara Ruang membantu belajar" class="grid gap-4 sm:grid-cols-2">
                <RuangCard class="space-y-4 p-5 sm:col-span-2 sm:p-6">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-secondary text-accent-foreground">
                        <CalendarCheck class="size-5" aria-hidden="true" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold">Ritme yang konsisten</h2>
                        <p class="mt-1 leading-6 text-muted-foreground">Susun langkah harian yang jelas untuk membangun kebiasaan belajar.</p>
                    </div>
                </RuangCard>
                <RuangCard class="space-y-4 p-5 sm:p-6">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-secondary text-accent-foreground">
                        <BookOpenCheck class="size-5" aria-hidden="true" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold">Materi beragam</h2>
                        <p class="mt-1 leading-6 text-muted-foreground">Baca, tonton, dan berlatih dari materi pilihan tutor.</p>
                    </div>
                </RuangCard>
                <RuangCard class="space-y-4 bg-secondary/50 p-5 sm:p-6">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-card text-primary shadow-sm">
                        <GraduationCap class="size-5" aria-hidden="true" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold">Belajar bersama</h2>
                        <p class="mt-1 leading-6 text-muted-foreground">Tutor dan siswa terhubung melalui aktivitas yang terarah.</p>
                    </div>
                </RuangCard>
            </section>
        </main>

        <footer class="border-t border-border px-4 py-5 text-center text-sm text-muted-foreground">
            Ruang — ruang untuk belajar bertumbuh.
        </footer>
    </div>
</template>
