<script setup>
import { reactive, onMounted } from "vue";
import axios from "axios";
import { RouterLink } from "vue-router";

const state = reactive({
  stats: {
    total_applications: 0,
    saved_jobs: 0,
  },
  recentApplications : [],
  recentSavedJobs: [],
  isLoading: true,
  error: "",
});




const fetchDashboard = async () => {
  state.isLoading = true;
  state.error = "";

  try {
    const response = await axios.get("/api/dashboard/job-seeker");

    state.stats = response.data.data.stats;
    state.recentApplications = response.data.data.recent_applications;
    state.recentSavedJobs = response.data.data.recent_saved_jobs;

  } catch (error) {
    console.error("Error fetching dashboard:", error);
    state.error = "Could not load your dashboard.";
  } finally {
    state.isLoading = false;
  }
};

onMounted(fetchDashboard);
</script>

<template>
  <section class="min-h-screen bg-slate-50 px-4 py-12">
    <div class="mx-auto max-w-7xl">
      <h1 class="text-3xl font-bold text-slate-900">
        My Dashboard
      </h1>

      <p class="mt-2 text-slate-600">
        An overview of your job search.
      </p>

      <div v-if="state.isLoading" class="mt-10 text-slate-600">
        Loading dashboard...
      </div>

      <div v-else-if="state.error" class="mt-10 text-red-600">
        {{ state.error }}
      </div>

      <div v-else class="mt-10 grid gap-6 md:grid-cols-2">

  <!-- Total Applications -->
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <p class="text-sm font-medium text-slate-500">
      Total Applications
    </p>

    <p class="mt-3 text-4xl font-bold text-slate-900">
      {{ state.stats.total_applications }}
    </p>
  </div>

  <!-- Saved Jobs -->
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <p class="text-sm font-medium text-slate-500">
      Saved Jobs
    </p>

    <p class="mt-3 text-4xl font-bold text-slate-900">
      {{ state.stats.saved_jobs }}
    </p>
  </div>

  <!-- Recent Applications -->
  <div class="md:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

    <div class="mb-6 flex items-center justify-between gap-4">
      <h2 class="text-xl font-bold text-slate-900">
        Recent Applications
      </h2>

      <RouterLink
        to="/applications"
        class="text-sm font-semibold text-indigo-600 hover:text-indigo-700"
      >
        View All →
      </RouterLink>
    </div>

    <p
      v-if="state.recentApplications.length === 0"
      class="text-slate-500"
    >
      You haven't applied for any jobs yet.
    </p>

    <div v-else class="divide-y divide-slate-100">

      <div
        v-for="application in state.recentApplications"
        :key="application.id"
        class="flex flex-wrap items-center justify-between gap-4 py-4"
      >
        <div class="min-w-0 flex-1">

          <RouterLink
            v-if="application.job"
            :to="`/jobs/${application.job.id}`"
            class="break-all font-semibold text-slate-900 hover:text-indigo-600"
          >
            {{ application.job.title }}
          </RouterLink>

          <p v-else class="font-semibold text-slate-500">
            Job unavailable
          </p>

          <p class="mt-1 text-sm text-slate-500">
            {{ application.job?.location || "Location unavailable" }}
          </p>

        </div>

        <span
          class="shrink-0 self-start rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium capitalize text-indigo-700"
        >
          {{ application.status }}
        </span>

      </div>

    </div>

      </div>

      <!-- Recent Saved Jobs -->
<div class="md:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

  <div class="mb-6 flex items-center justify-between gap-4">
    <h2 class="text-xl font-bold text-slate-900">
      Recent Saved Jobs
    </h2>

    <RouterLink
      to="/saved-jobs"
      class="text-sm font-semibold text-indigo-600 hover:text-indigo-700"
    >
      View All →
    </RouterLink>
  </div>

  <p
    v-if="state.recentSavedJobs.length === 0"
    class="text-slate-500"
  >
    You haven't saved any jobs yet.
  </p>

  <div v-else class="divide-y divide-slate-100">

    <div
      v-for="savedJob in state.recentSavedJobs"
      :key="savedJob.id"
      class="flex flex-wrap items-center justify-between gap-4 py-4"
    >
      <div class="min-w-0 flex-1">

        <RouterLink
          v-if="savedJob.job"
          :to="`/jobs/${savedJob.job.id}`"
          class="break-all font-semibold text-slate-900 hover:text-indigo-600"
        >
          {{ savedJob.job.title }}
        </RouterLink>

        <p v-else class="font-semibold text-slate-500">
          Job unavailable
        </p>

        <p class="mt-1 text-sm text-slate-500">
          {{ savedJob.job?.location || "Location unavailable" }}
        </p>

      </div>

      <span
        class="shrink-0 rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-700"
      >
        Saved
      </span>

    </div>

  </div>

</div>
      
  </div>

</div>
</section>
</template>