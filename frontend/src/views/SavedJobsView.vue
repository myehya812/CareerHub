<script setup>


import { reactive, onMounted, ref } from "vue";
import { RouterLink } from "vue-router";
import { useToast } from "vue-toastification";
import PulseLoader from "vue-spinner/src/PulseLoader.vue";
import axios from "axios";


const toast = useToast();

const removingSavedId = ref(null);

const state = reactive({
    savedJobs: [],
    isLoading: true,
});

const fetchSavedJobs = async () => {
    state.isLoading = true;

    try {
        const response = await axios.get("/api/saved-jobs");

        state.savedJobs = response.data.saved_jobs;
    } catch (error) {
        console.error("Error fetching saved jobs:", error);
        toast.error("Could not load saved jobs.");
    } finally {
        state.isLoading = false;
    }
};


const removeSavedJob = async (savedJob) => {
    if (removingSavedId.value !== null) {
        return;
    }

    removingSavedId.value = savedJob.id;

    try {
        await axios.get("/sanctum/csrf-cookie");

        await axios.delete(`/api/jobs/${savedJob.job_listing_id}/save`);

        state.savedJobs = state.savedJobs.filter(
            (item) => item.id !== savedJob.id,
        );

        toast.success("Job removed from saved jobs.");
    } catch (error) {
        console.error("Error removing saved job:", error);

        if (error.response?.status === 404) {
            await fetchSavedJobs();
            toast.error("This job is no longer in your saved jobs.");
        } else if (error.response?.status === 403) {
            toast.error("You are not allowed to remove this saved job.");
        } else {
            toast.error("Could not remove saved job.");
        }
    } finally {
        removingSavedId.value = null;
    }
};






onMounted(() => {
    fetchSavedJobs();
});



</script>


<template>
    <section class="mx-auto max-w-7xl px-6 py-10">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-900">My Saved Jobs</h1>

            <p class="mt-2 text-slate-500">
                Jobs you saved to revisit later.
            </p>
        </div>

        <div v-if="state.isLoading" class="flex justify-center py-20">
            <PulseLoader color="#4F46E5" />
        </div>

        <div v-else-if="state.savedJobs.length === 0"
            class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">
            <h2 class="text-xl font-semibold text-slate-900">
                No saved jobs yet
            </h2>

            <p class="mt-2 text-slate-500">
                Browse available jobs and save the ones you're interested in.
            </p>

            <RouterLink to="/jobs"
                class="mt-6 inline-block rounded-xl bg-indigo-600 px-5 py-3 font-semibold text-white transition hover:bg-indigo-700">
                Find Jobs
            </RouterLink>
        </div>

        <div v-else class="grid gap-5 md:grid-cols-2">
            <div v-for="savedJob in state.savedJobs" :key="savedJob.id"
                class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <span class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-sm font-semibold text-indigo-700">
                    {{ savedJob.job_listing.type }}
                </span>

                <h2 class="mt-4 text-xl font-bold text-slate-900">
                    {{ savedJob.job_listing.title }}
                </h2>

                <p class="mt-2 text-slate-500">
                    {{ savedJob.job_listing.location }}
                </p>

                <div class="mt-6 flex flex-wrap gap-3">


                    <RouterLink :to="`/jobs/${savedJob.job_listing.id}`"
                        class="inline-block rounded-xl bg-indigo-600 px-5 py-3 font-semibold text-white transition hover:bg-indigo-700">
                        View Job
                    </RouterLink>

                    <button @click="removeSavedJob(savedJob)" :disabled="removingSavedId !== null"
                        class="rounded-xl border border-red-200 px-5 py-3 font-semibold text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50">
                        {{
                            removingSavedId === savedJob.id
                                ? "Removing..."
                        : "Remove"
                        }}
                    </button>
                </div>

            </div>
        </div>
    </section>
</template>
