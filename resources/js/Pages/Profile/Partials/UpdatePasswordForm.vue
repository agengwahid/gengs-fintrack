<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

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
            if (form.errors.current_password) {
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
            <div>
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
                <InputLabel for="password" value="Kata Sandi Baru" />

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
                    value="Konfirmasi Kata Sandi Baru"
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
                    {{ form.processing ? 'Memproses...' : 'Ubah Kata Sandi' }}
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
                        Kata sandi berhasil diperbarui.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
