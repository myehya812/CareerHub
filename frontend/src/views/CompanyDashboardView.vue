<script setup>
import { reactive, onMounted } from "vue";
import { RouterLink } from "vue-router";
import axios from "axios";

const state = reactive({
    stats: {
        total_jobs: 0,
        total_applications: 0,
    },
    recentJobs: [],
    recentApplications: [],
    isLoading: true,
    error: "",
});

const fetchDashboard = async () => {
    state.isLoading = true;
    state.error = "";

    try {
        const response = await axios.get("/api/dashboard/company");

        state.stats = response.data.data.stats;
        state.recentJobs = response.data.data.recent_jobs;
        state.recentApplications = response.data.data.recent_applications;
    } catch (error) {
        console.error("Error fetching company dashboard:", error);
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
                Company Dashboard
            </h1>

            <p class="mt-2 text-slate-600">
                An overview of your recruitment activity.
            </p>

            <div v-if="state.isLoading" class="mt-10 text-slate-600">
                Loading dashboard...
            </div>

            <div v-else-if="state.error" class="mt-10 text-red-600">
                {{ state.error }}
            </div>

            <div v-else class="mt-10 grid gap-6 md:grid-cols-2">

                <!-- Published Jobs -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">
                        Published Jobs
                    </p>

                    <p class="mt-3 text-4xl font-bold text-slate-900">
                        {{ state.stats.total_jobs }}
                    </p>
                </div>

                <!-- Applications Received -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">
                        Applications Received
                    </p>

                    <p class="mt-3 text-4xl font-bold text-slate-900">
                        {{ state.stats.total_applications }}
                    </p>
                </div>

                <!-- Recent Jobs -->
                <div class="md:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <div class="mb-6 flex items-center justify-between gap-4">
                        <h2 class="text-xl font-bold text-slate-900">
                            Recent Jobs
                        </h2>

                        <RouterLink to="/jobs/add" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                            Post a Job →
                        </RouterLink>
                    </div>

                    <p v-if="state.recentJobs.length === 0" class="text-slate-500">
                        You haven't created any jobs yet.
                    </p>

                    <div v-else class="divide-y divide-slate-100">

                        <div v-for="job in state.recentJobs" :key="job.id"
                            class="flex flex-wrap items-center justify-between gap-4 py-4">
                            <div class="min-w-0 flex-1">

                                <RouterLink :to="`/jobs/${job.id}`"
                                    class="break-all font-semibold text-slate-900 hover:text-indigo-600">
                                    {{ job.title }}
                                </RouterLink>

                                <p class="mt-1 text-sm capitalize text-slate-500">
                                    Status: {{ job.status }}
                                </p>

                            </div>

                            <RouterLink :to="`/jobs/${job.id}/applicants`"
                                class="shrink-0 rounded-full bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100">
                                {{ job.applications_count }}
                                {{ job.applications_count === 1 ? "Applicant" : "Applicants" }}
                            </RouterLink>

                        </div>

                    </div>

                </div>

                <!-- Recent Applicants -->
                <div class="md:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <div class="mb-6 flex items-center justify-between gap-4">
                        <h2 class="text-xl font-bold text-slate-900">
                            Recent Applicants
                        </h2>
                    </div>

                    <p v-if="state.recentApplications.length === 0" class="text-slate-500">
                        No applications received yet.
                    </p>

                    <div v-else class="divide-y divide-slate-100">

                        <div v-for="application in state.recentApplications" :key="application.id"
                            class="flex flex-wrap items-center justify-between gap-4 py-4">
                            <div class="min-w-0 flex-1">

                                <p class="font-semibold text-slate-900">
                                    {{ application.user?.name || "Applicant unavailable" }}
                                </p>

                                <p class="mt-1 break-all text-sm text-slate-500">
                                    Applied for:
                                    {{ application.job?.title || "Job unavailable" }}
                                </p>

                                <span
                                    class="mt-2 inline-block rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium capitalize text-indigo-700">
                                    {{ application.status }}
                                </span>

                            </div>

                            <RouterLink v-if="application.job"
                                :to="`/jobs/${application.job.id}/applicants`"
                                class="shrink-0 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                View Applicants
                            </RouterLink>

                        </div>

                    </div>

                </div>

            </div>
        </div>
    </section>
</template>