<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    user: {
        id: number;
        role: string;
        status: string;
    };
}>();

const showResetForm = ref(false);
const statusForm = useForm({
    status: props.user.status === 'active' ? 'inactive' : 'active',
});
const accessForm = useForm({
    password: '',
    password_confirmation: '',
});

function changeStatus() {
    statusForm.status = props.user.status === 'active' ? 'inactive' : 'active';
    statusForm.patch(route('admin.users.status', props.user.id), { preserveScroll: true });
}

function resetAccess() {
    accessForm.put(route('admin.users.access.reset', props.user.id), {
        preserveScroll: true,
        onSuccess: () => {
            accessForm.reset();
            showResetForm.value = false;
        },
        onFinish: () => accessForm.reset(),
    });
}
</script>

<template>
    <div v-if="user.role !== 'admin'" class="flex min-w-52 flex-col items-start gap-2">
        <Button type="button" size="sm" variant="outline" :disabled="statusForm.processing" @click="changeStatus">
            {{ user.status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
        </Button>
        <InputError :message="statusForm.errors.status" />

        <Button type="button" size="sm" variant="ghost" @click="showResetForm = !showResetForm">
            {{ showResetForm ? 'Tutup reset akses' : 'Reset akses' }}
        </Button>
        <form v-if="showResetForm" class="grid w-full gap-2 rounded-lg border border-border p-3" @submit.prevent="resetAccess">
            <div class="grid gap-1">
                <Label :for="`password-${user.id}`" class="text-xs">Kata sandi baru</Label>
                <Input
                    :id="`password-${user.id}`"
                    v-model="accessForm.password"
                    type="password"
                    autocomplete="new-password"
                    required
                    :aria-invalid="Boolean(accessForm.errors.password)"
                />
                <InputError :message="accessForm.errors.password" />
            </div>
            <div class="grid gap-1">
                <Label :for="`password-confirmation-${user.id}`" class="text-xs">Konfirmasi kata sandi</Label>
                <Input
                    :id="`password-confirmation-${user.id}`"
                    v-model="accessForm.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    required
                />
            </div>
            <Button type="submit" size="sm" :disabled="accessForm.processing">Simpan kata sandi</Button>
        </form>
    </div>
    <span v-else class="text-xs text-muted-foreground">Akun admin dilindungi</span>
</template>
