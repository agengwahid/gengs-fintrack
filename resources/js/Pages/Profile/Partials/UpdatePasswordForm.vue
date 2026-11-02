<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Sparkles } from '@lucide/vue';

const props = defineProps({
    hasPassword: {
        type: Boolean,
        default: true,
    },
});

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password && currentPasswordInput.value) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <form @submit.prevent="updatePassword" class="space-y-4 max-w-xl">
            <!-- Alert jika akun Google belum memiliki kata sandi -->
            <div
                v-if="!hasPassword"
                class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 space-y-1"
            >
                <div class="font-bold flex items-center gap-1.5">
                    <Sparkles class="w-4 h-4 text-emerald-600" />
                    <span>Akun Terhubung dengan Google</span>
                </div>
                <p class="text-emerald-700">
                    Anda saat ini terdaftar menggunakan akun Google dan belum memiliki kata sandi lokal FinTrack. Buat kata sandi baru di bawah ini jika Anda juga ingin mengaktifkan opsi login manual menggunakan email & kata sandi.
                </p>
            </div>

            <!-- Field kata sandi saat ini hanya ditampilkan jika sudah punya sandi -->
            <div v-if="hasPassword">
                <InputLabel for="current_password" value="Kata Sandi Saat Ini" />

                <TextInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="mt-1 block w-full text-sm"
                    autocomplete="current-password"
                    placeholder="Masukkan kata sandi lama"
                />

                <InputError
                    :message="form.errors.current_password"
                    class="mt-1"
                />
            </div>

            <div>
                <InputLabel for="password" :value="hasPassword ? 'Kata Sandi Baru' : 'Kata Sandi FinTrack'" />

                <TextInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full text-sm"
                    autocomplete="new-password"
                    placeholder="Minimal 8 karakter kombinasi"
                />

                <InputError :message="form.errors.password" class="mt-1" />
            </div>

            <div>
                <InputLabel
                    for="password_confirmation"
                    :value="hasPassword ? 'Konfirmasi Kata Sandi Baru' : 'Konfirmasi Kata Sandi'"
                />

                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full text-sm"
                    autocomplete="new-password"
                    placeholder="Ulangi kata sandi baru"
                />

                <InputError
                    :message="form.errors.password_confirmation"
                    class="mt-1"
                />
            </div>

            <div class="flex items-center gap-4 pt-2">
                <PrimaryButton :disabled="form.processing" class="!bg-emerald-600 hover:!bg-emerald-700">
                    {{ form.processing ? 'Memproses...' : (hasPassword ? 'Ubah Kata Sandi' : 'Simpan Kata Sandi') }}
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out duration-200"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out duration-200"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-xs font-semibold text-emerald-600"
                    >
                        {{ hasPassword ? 'Kata sandi berhasil diperbarui.' : 'Kata sandi berhasil dibuat! Anda sekarang bisa masuk secara manual.' }}
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
