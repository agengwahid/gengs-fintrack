<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-4">
        <p class="text-xs text-slate-500 max-w-xl leading-relaxed">
            Setelah akun dihapus, seluruh data rekening, transaksi, kategori, anggaran, dan log sinkronisasi akan dihapus secara permanen dari basis data. Pastikan Anda telah mengekspor laporan keuangan yang diperlukan sebelum melanjutkan.
        </p>

        <div>
            <DangerButton @click="confirmUserDeletion" class="!text-xs">
                Hapus Akun Pengguna
            </DangerButton>
        </div>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-base font-bold text-slate-900">
                    Apakah Anda yakin ingin menghapus akun?
                </h2>

                <p class="mt-2 text-xs text-slate-500 leading-relaxed">
                    Tindakan ini tidak dapat dibatalkan. Masukkan kata sandi akun Anda untuk mengonfirmasi penghapusan permanen.
                </p>

                <div class="mt-4">
                    <InputLabel
                        for="password"
                        value="Kata Sandi Akun"
                        class="sr-only"
                    />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-3/4 text-sm"
                        placeholder="Masukkan kata sandi untuk konfirmasi"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeModal" class="!text-xs">
                        Batal
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3 !text-xs"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        {{ form.processing ? 'Menghapus...' : 'Ya, Hapus Akun Permanen' }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
