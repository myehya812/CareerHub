<script setup>
import router from '@/router';
import { reactive, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useToast } from 'vue-toastification';
import axios from 'axios';

// Gives us information from the current URL.
const route = useRoute();

// Example:
// URL = /jobs/edit/2
// jobId = 2
const jobId = route.params.id;

const toast = useToast();

/*
  This form matches our real MySQL job_listings table.

  Old tutorial:
  salary: "$50K - $60K"

  CareerHub:
  salary_min: 50000
  salary_max: 60000
  currency: "USD"
*/
const form = reactive({
  title: '',
  type: 'Full-Time',
  location: '',
  description: '',
  salary_min: null,
  salary_max: null,
  currency: 'USD',
  status: 'active',
});

// Used to show the loading state while we fetch the job.
const state = reactive({
  isLoading: true,
});

/*
  Send the edited job to Laravel.

  IMPORTANT:
  PUT /api/jobs/{id} has NOT been built yet.
  We will create that in Milestone 7.
*/
const handleSubmit = async () => {
  const updatedJob = {
    title: form.title,
    type: form.type,
    location: form.location,
    description: form.description,
    salary_min: form.salary_min,
    salary_max: form.salary_max,
    currency: form.currency,
    status: form.status,
  };

  try {
    // Example:
    // jobId = 2
    //
    // Request becomes:
    // PUT /api/jobs/2
    const response = await axios.put(`/api/jobs/${jobId}`, updatedJob);

    toast.success('Job updated successfully');

    // Go back to the updated job details page.
    router.push(`/jobs/${response.data.id}`);
  } catch (error) {
    console.error('Error updating job', error);

    toast.error('The job could not be updated');
  }
};

/*
  When this page opens, load the existing job from Laravel.

  Example:
  /jobs/edit/2
       ↓
  GET /api/jobs/2
       ↓
  Laravel returns job #2
       ↓
  Fill the form with its current values
*/
onMounted(async () => {
  try {
    const response = await axios.get(`/api/jobs/${jobId}`);

    const job = response.data;

    // Populate the form with existing database values.
    form.title = job.title;
    form.type = job.type;
    form.location = job.location;
    form.description = job.description;
    form.salary_min = job.salary_min;
    form.salary_max = job.salary_max;
    form.currency = job.currency;
    form.status = job.status;
  } catch (error) {
    console.error('Error fetching job', error);

    toast.error('Could not load this job');
  } finally {
    // Hide the loading screen whether the request succeeded or failed.
    state.isLoading = false;
  }
});
</script>

<template>
  <!-- Show the form after the job has loaded. -->
  <section v-if="!state.isLoading" class="py-12 sm:py-16">
    <div class="mx-auto max-w-3xl px-6">

      <!-- Page heading -->
      <div class="mb-8">
        <p
          class="text-sm font-semibold uppercase tracking-wider text-indigo-600"
        >
          Employers
        </p>

        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
          Edit job opportunity
        </h1>

        <p class="mt-3 text-slate-600">
          Update the role information candidates will see on CareerHub.
        </p>
      </div>

      <!--
        prevent stops the browser from refreshing.
        Vue runs handleSubmit() instead.
      -->
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
            Update the basic information about this opportunity.
          </p>
        </div>

        <!-- Job title -->
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
            class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
          />
        </div>

        <!-- Employment type + location -->
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
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
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
              class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            />
          </div>

        </div>

        <!-- Description -->
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
            class="w-full resize-none rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
          ></textarea>
        </div>

        <div class="border-t border-slate-200"></div>

        <!-- Compensation -->
        <div>
          <h2 class="text-xl font-bold text-slate-900">
            Compensation
          </h2>

          <p class="mt-1 text-sm text-slate-500">
            Update the salary range shown to candidates.
          </p>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">

          <!-- Minimum salary -->
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
              class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            />
          </div>

          <!-- Maximum salary -->
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
              class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            />
          </div>

          <!-- Currency -->
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
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            >
              <option value="USD">USD</option>
              <option value="EUR">EUR</option>
              <option value="LBP">LBP</option>
            </select>
          </div>

        </div>

        <div class="border-t border-slate-200"></div>

        <!-- Job Status -->
        <div>
          <label
            for="status"
            class="mb-2 block text-sm font-semibold text-slate-700"
          >
            Job status
          </label>

          <select
            id="status"
            v-model="form.status"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
          >
            <option value="active">Active</option>
            <option value="draft">Draft</option>
            <option value="closed">Closed</option>
          </select>

          <p class="mt-2 text-sm text-slate-500">
            Active jobs are available to candidates. Closed jobs are no longer accepting applications.
          </p>
        </div>

        <!-- Buttons -->
        <div
          class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end"
        >
          <!-- Cancel editing and return to the job page. -->
          <button
            type="button"
            @click="router.push(`/jobs/${jobId}`)"
            class="rounded-xl border border-slate-300 px-6 py-3 font-semibold text-slate-700 transition hover:bg-slate-50"
          >
            Cancel
          </button>

          <!-- Submit the updated job to Laravel. -->
          <button
            type="submit"
            class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white transition hover:bg-indigo-700"
          >
            Save Changes
          </button>
        </div>

      </form>
    </div>
  </section>

  <!-- Show while GET /api/jobs/{id} is loading. -->
  <div v-else class="flex justify-center py-20">
    Loading job...
  </div>
</template>