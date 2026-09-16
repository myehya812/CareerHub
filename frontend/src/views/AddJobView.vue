<script setup>
import router from '@/router';
import { reactive } from 'vue';
import { useToast } from 'vue-toastification';
import axios from 'axios';

const toast = useToast();

const form = reactive({
  title: '',
  type: 'Full-Time',
  location: '',
  description: '',

  salary_min: null,
  salary_max: null,

  currency: 'USD',
});

const handleSubmit = async () => {
  const newJob = {
    title: form.title,
    type: form.type,
    location: form.location,
    description: form.description,
    salary_min: form.salary_min,
    salary_max: form.salary_max,
    currency: form.currency,

    status: 'active',
  };

  try {
    const response = await axios.post('/api/jobs', newJob);

    toast.success('Job published successfully');

    router.push(`/jobs/${response.data.id}`);
  } catch (error) {
    console.error('Error creating job', error);

    toast.error('The job could not be published');
  }
};
</script>

<template>
  <section class="py-12 sm:py-16">
    <div class="mx-auto max-w-3xl px-6">

      <div class="mb-8">
        <p
          class="text-sm font-semibold uppercase tracking-wider text-indigo-600"
        >
          Employers
        </p>

        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
          Post a new opportunity
        </h1>

        <p class="mt-3 max-w-2xl text-slate-600">
          Share the role, compensation, and location so candidates can decide
          whether the opportunity is right for them.
        </p>
      </div>

      <form
        @submit.prevent="handleSubmit"
        class="space-y-8 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
      >

        <!-- Job Details -->

        <div>
          <h2 class="text-xl font-bold text-slate-900">
            Job details
          </h2>

          <p class="mt-1 text-sm text-slate-500">
            Tell candidates what position you are hiring for.
          </p>
        </div>

        <div>
          <label
            for="title"
            class="mb-2 block text-sm font-semibold text-slate-700"
          >
            Job title
          </label>

          <input
            id="title"
            v-model="form.title"
            type="text"
            required
            placeholder="e.g. Junior Frontend Developer"
            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
          />
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">

          <div>
            <label
              for="type"
              class="mb-2 block text-sm font-semibold text-slate-700"
            >
              Employment type
            </label>

            <select
              id="type"
              v-model="form.type"
              required
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            >
              <option value="Full-Time">Full-Time</option>
              <option value="Part-Time">Part-Time</option>
              <option value="Internship">Internship</option>
              <option value="Contract">Contract</option>
            </select>
          </div>

          <div>
            <label
              for="location"
              class="mb-2 block text-sm font-semibold text-slate-700"
            >
              Location
            </label>

            <input
              id="location"
              v-model="form.location"
              type="text"
              required
              placeholder="e.g. Beirut, Lebanon or Remote"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            />
          </div>

        </div>

        <div>
          <label
            for="description"
            class="mb-2 block text-sm font-semibold text-slate-700"
          >
            Job description
          </label>

          <textarea
            id="description"
            v-model="form.description"
            rows="6"
            required
            placeholder="Describe the role, responsibilities, and what you are looking for..."
            class="w-full resize-none rounded-xl border border-slate-300 px-4 py-3 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
          ></textarea>
        </div>

        <div class="border-t border-slate-200"></div>

        <!-- Compensation -->

        <div>
          <h2 class="text-xl font-bold text-slate-900">
            Compensation
          </h2>

          <p class="mt-1 text-sm text-slate-500">
            Add a salary range so candidates know what to expect.
          </p>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">

          <div>
            <label
              for="salary_min"
              class="mb-2 block text-sm font-semibold text-slate-700"
            >
              Minimum salary
            </label>

            <input
              id="salary_min"
              v-model.number="form.salary_min"
              type="number"
              min="0"
              placeholder="40000"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            />
          </div>

          <div>
            <label
              for="salary_max"
              class="mb-2 block text-sm font-semibold text-slate-700"
            >
              Maximum salary
            </label>

            <input
              id="salary_max"
              v-model.number="form.salary_max"
              type="number"
              min="0"
              placeholder="55000"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            />
          </div>

          <div>
            <label
              for="currency"
              class="mb-2 block text-sm font-semibold text-slate-700"
            >
              Currency
            </label>

            <select
              id="currency"
              v-model="form.currency"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            >
              <option value="USD">USD</option>
              <option value="EUR">EUR</option>
              <option value="LBP">LBP</option>
            </select>
          </div>

        </div>

        <div class="border-t border-slate-200"></div>

        <div class="rounded-xl bg-indigo-50 p-5">
          <h3 class="font-semibold text-indigo-900">
            Company profile
          </h3>

          <p class="mt-1 text-sm leading-6 text-indigo-700">
            Company information will later be connected automatically to the
            company account that publishes this job.
          </p>
        </div>

        <div
          class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end"
        >
          <button
            type="button"
            @click="router.push('/jobs')"
            class="rounded-xl border border-slate-300 px-6 py-3 font-semibold text-slate-700 transition hover:bg-slate-50"
          >
            Cancel
          </button>

          <button
            type="submit"
            class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white shadow-sm transition hover:bg-indigo-700"
          >
            Publish Job
          </button>
        </div>

      </form>
    </div>
  </section>
</template>