<script setup>

import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const router = useRouter();
const toast = useToast();


const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'job_seeker',

});


const loading = ref(false);
const error = ref('');

async function handleSubmit() {
  loading.value = true;
  error.value = '';


  try {
    await axios.post('/api/register', form);

    toast.success('Account created successfully');

    router.push('/login');
  } catch (err) {
    if (err.response?.status === 422) {
      error.value = 'Please check the information you entered.';
    } else {
      error.value = 'Unable to create account. Please try again.';
    }
  } finally {
    loading.value = false;
  }

};

</script>



<template>
  <section class="min-h-[calc(100vh-64px)] bg-slate-50 px-4 py-16">
    <div class="mx-auto max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900">
          Create your CareerHub account
        </h1>

        <p class="mt-2 text-sm text-slate-600">
          Create an account to get started.
        </p>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-5">
        <div>
          <label for="name" class="mb-2 block text-sm font-medium text-slate-700">
            Name
          </label>

          <input id="name" v-model="form.name" type="text" required autocomplete="name"
            class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            placeholder="Your name" />
        </div>

        <div>
          <label for="email" class="mb-2 block text-sm font-medium text-slate-700">
            Email
          </label>

          <input id="email" v-model="form.email" type="email" required autocomplete="email"
            class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            placeholder="you@example.com" />
        </div>


        <div>
          <label for="role" class="mb-2 block text-sm font-medium text-slate-700">

            Account Type
          </label>

          <select id="role" v-model="form.role" required
            class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
              <option value="job_seeker">
                Job Seeker - I am looking for work
              </option>

              <option value="company">
                Company - I am hiring
              </option>
          </select>

        </div>


        <div>
          <label for="password" class="mb-2 block text-sm font-medium text-slate-700">
            Password
          </label>

          <input id="password" v-model="form.password" type="password" required minlength="8"
            autocomplete="new-password"
            class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            placeholder="At least 8 characters" />
        </div>

        <div>
          <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-700">
            Confirm Password
          </label>

          <input id="password_confirmation" v-model="form.password_confirmation" type="password" required minlength="8"
            autocomplete="new-password"
            class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            placeholder="Enter the password again" />
        </div>

        <p v-if="error" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
          {{ error }}
        </p>

        <button type="submit" :disabled="loading"
          class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
          {{ loading ? 'Creating account...' : 'Create account' }}
        </button>
      </form>
    </div>
  </section>
</template>