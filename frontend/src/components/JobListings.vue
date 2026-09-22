<script setup>
import { RouterLink } from "vue-router";
import JobListing from "./JobListing.vue";
import { reactive, defineProps, onMounted } from "vue";
import PulseLoader from "vue-spinner/src/PulseLoader.vue";
import axios from "axios";

defineProps({
  limit: Number,

  showButton: {
    type: Boolean,
    default: false,
  },

  showSearch: {
    type: Boolean,
    default: false,
  },
});

const state = reactive({
  jobs: [],
  isLoading: true,

  searchTerm: '',
  location: '',
  type: '',
  sort: 'newest',


  currentPage: 1,
  lastPage: 1,
  total: 0,

});

const fetchJobs = async (page = 1) => {
  state.isLoading = true;

  try {
    const response = await axios.get("/api/jobs", {
      params: {
        search: state.searchTerm,
        location: state.location,
        type: state.type,
        sort: state.sort,
        page,
      },
    });

    state.jobs = response.data.data;
    state.currentPage = response.data.current_page;
    state.lastPage = response.data.last_page;
    state.total = response.data.total;

  } catch (error) {
    console.error("Error fetching  jobs", error);
  } finally {
    state.isLoading = false;
  }
};

onMounted(fetchJobs);
</script>


<template>
  <section class="bg-slate-50 px-4 py-16">
    <div class="mx-auto max-w-7xl">

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

      <!-- Search and discovery filters -->
      <form v-if="showSearch" @submit.prevent="fetchJobs(1)" class="mx-auto mb-10 max-w-4xl">
        <div class="grid gap-3 md:grid-cols-4">

          <input v-model="state.searchTerm" type="text" placeholder="Search jobs..."
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-indigo-500" />

          <input v-model="state.location" type="text" placeholder="Location..."
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-indigo-500" />

          <select v-model="state.type"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-indigo-500">
            <option value="">
              All Job Types
            </option>

            <option value="Full-Time">
              Full-Time
            </option>

            <option value="Part-Time">
              Part-Time
            </option>

            <option value="Internship">
              Internship
            </option>

            <option value="Contract">
              Contract
            </option>
          </select>

          <select v-model="state.sort" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none 
            transition focus:border-indigo-500">
            <option value="newest">
              Newest
            </option>

            <option value="oldest">
              Oldest
            </option>

            <option value="salary_low">
              Salary: Low to High
            </option>

            <option value="salary_high">
              Salary: High to Low
            </option>
          </select>

        </div>

        <div class="mt-4 text-center">
          <button type="submit"
            class="rounded-xl bg-indigo-600 px-8 py-3 font-semibold text-white transition hover:bg-indigo-700">
            Search Jobs
          </button>
        </div>
      </form>


      <p class="mb-6 text-sm font-medium text-slate-600" v-if="showSearch && !state.isLoading"> {{ state.total }} {{
        state.total === 1 ? 'job' : 'jobs' }} found</p>

      <!-- Laravel request is still running -->
      <div v-if="state.isLoading" class="flex justify-center py-12">
        <PulseLoader color="#4F46E5" />
      </div>

      <!-- Jobs returned by Laravel -->
      <div v-else-if="state.jobs.length === 0"
        class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">

        <h3 class="text-lg font-semibold text-slate-900">
          No jobs found
        </h3>

        <p class="mt-2 text-slate-500">
          Try changing your search or filters.
        </p>
      </div>

      <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 ">

        <JobListing v-for="job in state.jobs.slice(0, limit || state.jobs.length)" :key="job.id" :job="job" />
      </div>


      <div v-if="showSearch && state.lastPage > 1 && !state.isLoading"
        class="mt-10 flex items-center justify-center gap-4">
       
        <button @click="fetchJobs(state.currentPage - 1)" :disabled="state.currentPage === 1"
          class="rounded-xl border border-slate-300 bg-white px-5 py-2 font-semibold text-slate-700 transition
           hover:border-indigo-300 hover:text-indigo-600 disabled:cursor-not-allowed disabled:opacity-50 ">Previous</button>
        <span>
          Page {{ state.currentPage }} of {{ state.lastPage }}
        </span>

        <button @click="fetchJobs(state.currentPage + 1)" :disabled="state.currentPage === state.lastPage" class="rounded-xl border border-slate-300 bg-white px-5 py-2 font-semibold text-slate-700 transition
           hover:border-indigo-300 hover:text-indigo-600 disabled:cursor-not-allowed disabled:opacity-50">

          Next</button>
      </div>

      <div v-if="showButton" class="mt-10 text-center">
        <RouterLink to="/jobs"
          class="inline-flex rounded-xl border border-slate-300 bg-white px-6 py-3 font-semibold text-slate-700 shadow-sm transition hover:border-indigo-300 hover:text-indigo-600">
          Explore All Jobs →
        </RouterLink>
      </div>

    </div>
  </section>
</template>
