<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import { computed } from 'vue';
import type { RuangRole } from '@/navigation/role-navigation';
import { useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const props = defineProps<{
    roles: RuangRole[];
}>();

const roleLabels: Record<string, string> = {
    tutor: 'Tutor',
    student: 'Siswa',
};

const roleOptions = computed(() =>
    props.roles.map((r) => ({
        value: r,
        label: roleLabels[r] ?? r,
    })),
);

const form = useForm({
    name: '',
    email: '',
    role: 'student' as RuangRole,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(route('admin.users.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('name', 'email', 'password', 'password_confirmation'),
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <form class="rounded-xl border border-border bg-card p-5 shadow-sm sm:p-6" @submit.prevent="submit">
        <div class="mb-5">
            <h2 class="text-lg font-semibold text-card-foreground">Tambah akun</h2>
            <p class="mt-1 text-sm text-muted-foreground">Buat akun tutor atau siswa. Akun baru aktif setelah disimpan.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="new-user-name">Nama lengkap</Label>
                <Input
                    id="new-user-name"
                    v-model="form.name"
                    autocomplete="name"
                    required
                    maxlength="255"
                    :aria-invalid="Boolean(form.errors.name)"
                />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="new-user-email">Email</Label>
                <Input
                    id="new-user-email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    required
                    maxlength="255"
                    :aria-invalid="Boolean(form.errors.email)"
                />
                <InputError :message="form.errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="new-user-role">Peran</Label>
                <SearchableSelect
                    id="new-user-role"
                    v-model="form.role"
                    :options="roleOptions"
                    placeholder="Pilih peran..."
                    aria-label="Peran"
                />
                <InputError :message="form.errors.role" />
            </div>

            <div class="grid gap-2">
                <Label for="new-user-password">Kata sandi awal</Label>
                <Input
                    id="new-user-password"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    required
                    :aria-invalid="Boolean(form.errors.password)"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="grid gap-2 sm:col-span-2">
                <Label for="new-user-password-confirmation">Konfirmasi kata sandi</Label>
                <Input
                    id="new-user-password-confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    required
                />
            </div>
        </div>

        <div class="mt-5 flex justify-end">
            <Button type="submit" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="mr-2 size-4 animate-spin" aria-hidden="true" />
                Buat akun
            </Button>
        </div>
    </form>
</template>
