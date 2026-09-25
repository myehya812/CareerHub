<script setup>
import PulseLoader from "vue-spinner/src/PulseLoader.vue";
import BackButton from "@/components/BackButton.vue";
import { reactive, onMounted, computed, ref } from "vue";
import { useRoute, RouterLink, useRouter } from "vue-router";
import { useToast } from "vue-toastification";
import axios from "axios";
import { useAuthStore } from "@/stores/auth";

const route = useRoute();
const router = useRouter();
const toast = useToast();

const auth = useAuthStore();

const jobId = route.params.id;
const isApplying = ref(false);
const hasApplied = ref(false);
const isSaved = ref(false);
const isCheckingSaved = ref(false);
const isUpdatingSaved = ref(false);
const saveStatusError = ref(false);

const state = reactive({
  job: {},
  isLoading: true,
});

const formattedSalary = computed(() => {
  const salary = state.job.salary;

  if (salary?.min == null || salary?.max == null) {
    return "Salary not specified";
  }

  const min = Number(salary.min);
  const max = Number(salary.max);

  if (Number.isNaN(min) || Number.isNaN(max)) {
    return "Salary not specified";
  }

  const formatNumber = (value) => {
    return new Intl.NumberFormat("en-US").format(value);
  };

  return `${salary.currency} ${formatNumber(min)} - ${formatNumber(max)}`;
});

const isOwner = computed(() => {
  return (
    auth.isAuthenticated &&
    auth.user?.role === "company" &&
    auth.user?.id === state.job.company?.id
  );
});

const deleteJob = async () => {
  const confirmDelete = window.confirm(
    "Are you sure you want to delete this job?",
  );

  if (!confirmDelete) return;

  try {
    await axios.delete(`/api/jobs/${jobId}`);

    toast.success("Job deleted successfully.");

    router.push("/jobs");
  } catch (error) {
    console.error("Error deleting job:", error);

    if (error.response?.status === 403) {
      toast.error("You are not allowed to delete this job.");
    } else {
      toast.error("The job could not be deleted");
    }
  }
};

const checkApplicationStatus = async () => {
  if (!auth.isAuthenticated || auth.user?.role !== 'job_seeker') {
    return;
  } try {
    const response = await axios.get(`/api/jobs/${jobId}/application-status`,);

    hasApplied.value = response.data.has_applied;


  } catch (error) {
    console.error('console.error("Error checking application status", error);')
  }

};


const checkSaveStatus = async () => {
  if (!auth.isAuthenticated || auth.user?.role !== "job_seeker") {
    return;
  }

  isCheckingSaved.value = true;
  saveStatusError.value = false

  try {
    const response = await axios.get(`/api/jobs/${jobId}/save-status`,);

    isSaved.value = response.data.is_saved === true;
  } catch (error) {
    console.error("Error checking saved job status", error);

    saveStatusError.value = true;
    toast.error("Could not check saved job status");
  } finally {
    isCheckingSaved.value = false;
  }
}

  const saveJob = async () => {
    if (!auth.isAuthenticated || auth.user?.role !== 'job_seeker' || isUpdatingSaved.value) {
      return;
    }

    isUpdatingSaved.value = true;

    try {
      await axios.get("/sanctum/csrf-cookie");

      const response = await axios.post(`/api/jobs/${jobId}/save`);

      isSaved.value = response.data.is_saved === true;

      toast.success(response.data.message);

    } catch (error) {
      console.error("Error saving jobs:", error);

      if (error.response?.status === 403) {
        toast.error("You are not allowed to save jobs.");
      }
      else if (error.response?.status === 404) {
        toast.error("This job no longer exists.");
      } else {
        toast.error("This job could not be saved");
      }
    } finally {
      isUpdatingSaved.value = false;
    }
  };

  const removeSavedJob = async () => {
    if (
      !auth.isAuthenticated ||
      auth.user?.role !== "job_seeker" ||
      isUpdatingSaved.value
    ) {
      return;
    }

    isUpdatingSaved.value = true;

    try {
      await axios.get("/sanctum/csrf-cookie");

      const response = await axios.delete(`/api/jobs/${jobId}/save`);

      isSaved.value = response.data.is_saved === true;

      toast.success(response.data.message);
    } catch (error) {
      console.error("Error removing saved job:", error);

      if (error.response?.status === 403) {
        toast.error("You are not allowed to remove this saved job.");
      } else if (error.response?.status === 404) {
        toast.error("This job is not saved.");
        await checkSaveStatus();
      } else {
        toast.error("The saved job could not be removed.");
      }
    } finally {
      isUpdatingSaved.value = false;
    }
  };





onMounted(async () => {
  try {
    const response = await axios.get(`/api/jobs/${jobId}`);
    state.job = response.data.data;
    await checkApplicationStatus();
    await checkSaveStatus();
  } catch (error) {
    console.error("Error fetching job", error);
  } finally {
    state.isLoading = false;
  }
});



const applyToJob = async () => {
  if (!auth.isAuthenticated || auth.user?.role !== "job_seeker") {
    return;
  }

  isApplying.value = true;

  try {
    await axios.get(`/sanctum/csrf-cookie`);

    await axios.post(`/api/jobs/${jobId}/applications`);

    hasApplied.value = true;

    toast.success("Application submitted successfully");
  } catch (error) {
    console.error("Error applying to job: ", error);

    if (error.response?.status === 409) {
      hasApplied.value = true;
      toast.error("You have already applied to this job,");
    } else if (error.response?.status === 403) {
      toast.error("You are not allowed to apply to this job.");
    } else {
      toast.error("The application could not be submitted");
    }
  } finally {
    isApplying.value = false;
  }
};
</script>

<template>
  <BackButton />

  <section v-if="!state.isLoading" class="mx-auto max-w-7xl px-6 py-10">
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
      <!-- Main Job Content -->
      <main class="lg:col-span-2">
        <!-- Job Header -->
        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
          <span class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-sm font-semibold text-indigo-700">
            {{ state.job.type }}
          </span>

          <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900">
            {{ state.job.title }}
          </h1>

          <div class="mt-4 flex items-center gap-2 text-slate-500">
            <i class="pi pi-map-marker text-indigo-500"></i>

            <span>
              {{ state.job.location }}
            </span>
          </div>

          <div class="mt-6">
            <p class="text-sm font-medium text-slate-500">Salary</p>

            <p class="mt-1 text-lg font-semibold text-slate-900">
              {{ formattedSalary }} / Year
            </p>
          </div>
        </div>

        <!-- Job Description -->
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
          <h2 class="text-xl font-bold text-slate-900">About the role</h2>

          <p class="mt-4 leading-7 text-slate-600">
            {{ state.job.description }}
          </p>
        </div>
      </main>

      <!-- Sidebar -->
      <aside class="space-y-6">
        <div class="rounded-2xl border border-indigo-100 bg-indigo-600 p-6 text-white shadow-sm">
          <h2 class="text-xl font-bold">Interested in this role?</h2>

          <p class="mt-2 text-sm leading-6 text-indigo-100">
            Apply now and let the company know you're interested in this role.
          </p>

          <button v-if="auth.isAuthenticated && auth.user?.role === 'job_seeker'" @click="applyToJob"
            :disabled="isApplying || hasApplied"
            class="mt-5 w-full rounded-xl bg-white px-4 py-3 font-semibold text-indigo-700 transition hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-70">
            {{ hasApplied ? "Already Applied" : isApplying ? " Applying..." : 'Apply Now' }}
          </button>

          <button v-if="auth.isAuthenticated && auth.user?.role === 'job_seeker'"
            @click="isSaved ? removeSavedJob() : saveJob()"
            :disabled="isCheckingSaved || isUpdatingSaved || saveStatusError"
            class="mt-3 w-full rounded-xl border border-white px-4 py-3 font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60">

            {{ isCheckingSaved ? "Checking..." : saveStatusError ? "Save status unavailable" : isUpdatingSaved ?
              "Updating..." : isSaved ? "Remove Saved Job" : "Save Job" }}
          </button>

        </div>

        <!-- Company Card -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 class="text-xl font-bold text-slate-900">About the company</h2>

          <p class="mt-3 leading-6 text-slate-600">
            Company profiles will appear here once company profiles are added.
          </p>
        </div>

        <!-- Only the company that published this job can manage it. -->
        <div v-if="isOwner" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 class="text-xl font-bold text-slate-900">Manage this job</h2>

          <p class="mt-2 text-sm leading-6 text-slate-500">
            You published this job, so you can edit or remove it.
          </p>

          <div class="mt-5 space-y-3">

            <RouterLink :to="`/jobs/${jobId}/applicants`"
              class="block w-full rounded-xl bg-slate-900 px-4 py-3 text-center font-semibold text-white transition hover:bg-slate-800">
              View Applicants
            </RouterLink>


            <RouterLink :to="`/jobs/edit/${jobId}`"
              class="block w-full rounded-xl bg-indigo-600 px-4 py-3 text-center font-semibold text-white transition hover:bg-indigo-700">
              Edit Job
            </RouterLink>

            <button @click="deleteJob"
              class="w-full rounded-xl border border-red-200 px-4 py-3 font-semibold text-red-600 transition hover:bg-red-50">
              Delete Job
            </button>
          </div>
        </div>
      </aside>
    </div>
  </section>

  <div v-else class="flex justify-center py-20">
    <PulseLoader color="#4F46E5" />
  </div>
</template>
