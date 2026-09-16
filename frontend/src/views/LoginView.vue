<script setup>

import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'vue-toastification';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const toast = useToast();
const auth = useAuthStore();

const form = reactive({
    email: '',
    password: '',
});

const loading = ref(false);
const error = ref('');

const handleSubmit = async() =>{
        loading.value = true;
        error.value = '';

    try {
        await auth.login(form);

        toast.success('Logged in successfully');

        router.push('/');
    } catch(err){
        if(err.response?.status === 401){
             error.value = 'Invalid email or password.';
        }else if(err.response?.status === 401){
            error.value = 'Please enter a valid email and password.';
        }else{
            error.value = 'Unable to log in . Please try again.';
        }
    } finally{
        loading.value = false;
    }

};

</script>


<template>
  <section class="min-h-[calc(100vh-64px)] bg-slate-50 px-4 py-16">
    <div
      class="mx-auto max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
    >
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900">
          Sign in to CareerHub
        </h1>

        <p class="mt-2 text-sm text-slate-600">
          Access your CareerHub account.
        </p>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-5">
        <div>
          <label
            for="email"
            class="mb-2 block text-sm font-medium text-slate-700"
          >
            Email
          </label>

          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            autocomplete="email"
            class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            placeholder="you@example.com"
          />
        </div>

        <div>
          <label
            for="password"
            class="mb-2 block text-sm font-medium text-slate-700"
          >
            Password
          </label>

          <input
            id="password"
            v-model="form.password"
            type="password"
            required
            autocomplete="current-password"
            class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            placeholder="Enter your password"
          />
        </div>

        <p
          v-if="error"
          class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"
        >
          {{ error }}
        </p>

        <button
          type="submit"
          :disabled="loading"
          class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
        >
          {{ loading ? 'Signing in...' : 'Sign in' }}
        </button>
      </form>
    </div>
  </section>
</template>