<script setup>
import { RouterLink } from 'vue-router';
import JobListing from './JobListing.vue';
import { reactive, defineProps, onMounted } from 'vue';
import PulseLoader from 'vue-spinner/src/PulseLoader.vue';
import axios from 'axios';

defineProps({
  limit: Number,
  showButton: {
    type: Boolean,
    default: false,
  },
});

const state = reactive({
  jobs: [],
  isLoading: true,
});

onMounted(async () => {
  try {
    const response = await axios.get('/api/jobs');
    state.jobs = response.data;
  } catch (error) {
    console.error('Error fetching jobs', error);
  } finally {
    state.isLoading = false;
  }
});
</script>

<template>
  <section class="bg-slate-50 px-4 py-16">
    <div class="mx-auto max-w-7xl">

      <!-- Section title -->
      <div class="mb-10 text-center">
        <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600">
          Opportunities
        </p>

        <h2 class="mt-2 text-3xl font-bold text-slate-900">
          Latest Jobs
        </h2>

        <p class="mt-3 text-slate-600">
          Explore opportunities from companies looking for new talent.
        </p>
      </div>

      <!--
        While Axios is waiting for Laravel,
        show a loading spinner instead of an empty page.
      -->
      <div
        v-if="state.isLoading"
        class="flex justify-center py-12"
      >
        <PulseLoader color="#4F46E5" />
      </div>

      <!-- Display the jobs after loading is complete. -->
      <div
        v-else
        class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
      >
        <JobListing
          v-for="job in state.jobs.slice(0, limit || state.jobs.length)"
          :key="job.id"
          :job="job"
        />
      </div>

      <!--
        This button only appears on the homepage because
        HomeView passes showButton="true".
      -->
      <div
        v-if="showButton"
        class="mt-10 text-center"
      >
        <RouterLink
          to="/jobs"
          class="inline-flex rounded-xl border border-slate-300 bg-white px-6 py-3 font-semibold text-slate-700 shadow-sm transition hover:border-indigo-300 hover:text-indigo-600"
        >
          Explore All Jobs →
        </RouterLink>
      </div>

    </div>
  </section>
</template>