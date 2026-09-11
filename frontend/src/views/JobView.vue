<script setup>
import PulseLoader from 'vue-spinner/src/PulseLoader.vue';
import BackButton from '@/components/BackButton.vue';
import { reactive, onMounted , computed  } from 'vue';
import { useRoute, RouterLink, useRouter } from 'vue-router';
import { useToast } from 'vue-toastification';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const jobId = route.params.id;

const state = reactive({
  job: {},
  isLoading: true,
});

const formattedSalary = computed(() => {
  const min = Number(state.job.salary_min);
  const max = Number(state.job.salary_max);

  
  if (Number.isNaN(min) || Number.isNaN(max)) {
    return 'Salary not specified';
  }

  
  const formatNumber = (value) => {
    return new Intl.NumberFormat('en-US').format(value);
  };
  return `${state.job.currency} ${formatNumber(min)} - ${formatNumber(max)}`;
});




const deleteJob = async () => {
  try {
    const confirm = window.confirm('Are you sure you want to delete this job?');
    if (confirm) {
      await axios.delete(`/api/jobs/${jobId}`);
      toast.success('Job Deleted Successfully');
      router.push('/jobs');
    }
  } catch (error) {
    console.error('Error deleting job', error);
    toast.error('Job Not Deleted');
  }
};

onMounted(async () => {
  try {
    const response = await axios.get(`/api/jobs/${jobId}`);
    state.job = response.data;
  } catch (error) {
    console.error('Error fetching job', error);
  } finally {
    state.isLoading = false;
  }
});
</script>
<template>
  <!-- Navigation back to the jobs page -->
  <BackButton />

  <!--
    Show the page only after Laravel has returned the job.
    Until then we show the loading spinner below.
  -->
  <section
    v-if="!state.isLoading"
    class="mx-auto max-w-7xl px-6 py-10"
  >
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">

      <!-- Main Job Content -->
      <main class="lg:col-span-2">

        <!-- Job Header -->
        <div
          class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
        >
          <!-- Job type -->
          <span
            class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-sm font-semibold text-indigo-700"
          >
            {{ state.job.type }}
          </span>

          <!-- Job title -->
          <h1
            class="mt-4 text-3xl font-bold tracking-tight text-slate-900"
          >
            {{ state.job.title }}
          </h1>

          <!-- Location -->
          <div class="mt-4 flex items-center gap-2 text-slate-500">
            <i class="pi pi-map-marker text-indigo-500"></i>

            <span>
              {{ state.job.location }}
            </span>
          </div>

          <!-- Salary -->
          <div class="mt-6">
            <p class="text-sm font-medium text-slate-500">
              Salary
            </p>

            <p class="mt-1 text-lg font-semibold text-slate-900">
              {{ formattedSalary }} / Year
            </p>
          </div>
        </div>

        <!-- Job Description -->
        <div
          class="mt-6 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
        >
          <h2 class="text-xl font-bold text-slate-900">
            About the role
          </h2>

          <p class="mt-4 leading-7 text-slate-600">
            {{ state.job.description }}
          </p>
        </div>

      </main>

      <!-- Sidebar -->
      <aside class="space-y-6">

        <!-- Apply Card -->
        <div
          class="rounded-2xl border border-indigo-100 bg-indigo-600 p-6 text-white shadow-sm"
        >
          <h2 class="text-xl font-bold">
            Interested in this role?
          </h2>

          <p class="mt-2 text-sm leading-6 text-indigo-100">
            Applications will be available once CareerHub user accounts are added.
          </p>

          <!--
            Disabled for now because authentication/applications
            have not been built yet.
          -->
          <button
            disabled
            class="mt-5 w-full cursor-not-allowed rounded-xl bg-white/80 px-4 py-3 font-semibold text-indigo-700"
          >
            Apply Now
          </button>
        </div>

        <!-- Company Card -->
        <div
          class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
        >
          <h2 class="text-xl font-bold text-slate-900">
            About the company
          </h2>

          <p class="mt-3 leading-6 text-slate-600">
            Company profiles will appear here once company accounts are added.
          </p>
        </div>

      </aside>
    </div>
  </section>

  <!-- Show while Axios is waiting for Laravel -->
  <div
    v-else
    class="flex justify-center py-20"
  >
    <PulseLoader color="#4F46E5" />
  </div>
</template>