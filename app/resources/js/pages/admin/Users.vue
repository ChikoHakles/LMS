<script setup lang="ts">
import UserCreateForm from '@/components/admin/UserCreateForm.vue';
import UserTable from '@/components/admin/UserTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';

type RuangRole = 'admin' | 'tutor' | 'student';
type UserPage = {
    data: Array<{ id: number; name: string; email: string; role: string; status: string; created_at: string }>;
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
    total: number;
};

defineProps<{
    users: UserPage;
    roles: RuangRole[];
}>();

const page = usePage<SharedData & { flash: { status?: string } }>();
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Akun pengguna', href: route('admin.users.index') }];
</script>

<template>
    <Head title="Akun pengguna" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header>
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-primary">Administrasi</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Akun pengguna</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                    Kelola akun tutor dan siswa, status akses, serta pengaturan ulang kata sandi.
                </p>
            </header>

            <div
                v-if="page.props.flash?.status"
                class="rounded-lg border border-success/30 bg-success-soft px-4 py-3 text-sm text-success"
                role="status"
                aria-live="polite"
            >
                {{ page.props.flash.status }}
            </div>

            <UserCreateForm :roles="roles" />
            <UserTable :users="users" />
        </div>
    </AppLayout>
</template>
