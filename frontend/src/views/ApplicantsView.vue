<script setup>
import { onMounted, reactive } from 'vue';
import { useRoute } from 'vue-router';
import { useToast } from 'vue-toastification';
import axios from 'axios';
import PulseLoader from 'vue-spinner/src/PulseLoader.vue';

const route = useRoute();

const toast = useToast();

const jobId = route.params.id;

const state = reactive({
  applications: [],
  isLoading: true,
  updatingApplicationId: null,
});


const statusClass = (status) => {
  if (status === 'accepted') {
    return 'bg-green-100 text-green-700';
  }

  if (status === 'rejected') {
    return 'bg-red-100 text-red-700';
  }

  return 'bg-amber-100 text-amber-700';
};


const updateStatus = async (application, status) => {
  state.updatingApplicationId = application.id;

  try {
    
    await axios.get('/sanctum/csrf-cookie');

    const response = await axios.patch(
      `/api/applications/${application.id}/status`,
      {
        status,
      },
    );

    
    application.status = response.data.application.status;

    toast.success(`Application ${status} successfully.`);
  } catch (error) {
    console.error('Error updating application status:', error);

    if (error.response?.status === 403) {
      toast.error('You are not allowed to update this application.');
    } else if (error.response?.status === 422) {
      toast.error('Invalid application status.');
    } else {
      toast.error('The application status could not be updated.');
    }
  } finally {
    state.updatingApplicationId = null;
  }
};


onMounted(async () => {
  try {
    
    const response = await axios.get(`/api/jobs/${jobId}/applications`);

    state.applications = response.data;
  } catch (error) {
    console.error('Error fetching applicants:', error);
  } finally {
    state.isLoading = false;
  }
});

</script>



<template>
  <section class="mx-auto max-w-5xl px-6 py-10">
    <h1 class="text-3xl font-bold text-slate-900">
      Job Applicants
    </h1>

    
    <div
      v-if="state.isLoading"
      class="flex justify-center py-20"
    >
      <PulseLoader color="#4F46E5" />
    </div>

    
    <div
      v-else-if="state.applications.length === 0"
      class="mt-8 rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm"
    >
      <p class="text-slate-600">
        No one has applied to this job yet.
      </p>
    </div>

  
    <div
      v-else
      class="mt-8 space-y-4"
    >
      <div
        v-for="application in state.applications"
        :key="application.id"
        class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
      >
        <h2 class="text-xl font-bold text-slate-900">
          {{ application.user.name }}
        </h2>

        <p class="mt-2 text-slate-500">
          {{ application.user.email }}
        </p>

        <div class="mt-4">
          <span
            :class="[
              'inline-flex rounded-full px-3 py-1 text-sm font-semibold capitalize',
              statusClass(application.status),
            ]"
          >
            {{ application.status }}
          </span>
        </div>

        <div class="mt-6 flex gap-3">
          <button
            @click="updateStatus(application, 'accepted')"
            :disabled="
              state.updatingApplicationId === application.id ||
              application.status === 'accepted'
            "
            class="rounded-lg bg-green-600 px-4 py-2 font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
          >
            Accept
          </button>

          <button
            @click="updateStatus(application, 'rejected')"
            :disabled="
              state.updatingApplicationId === application.id ||
              application.status === 'rejected'
            "
            class="rounded-lg bg-red-600 px-4 py-2 font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
          >
            Reject
          </button>
        </div>
      </div>
    </div>
  </section>
</template>