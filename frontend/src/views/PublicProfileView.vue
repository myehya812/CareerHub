<script setup>
import { onMounted, reactive } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import PulseLoader from 'vue-spinner/src/PulseLoader.vue';

const route = useRoute();

const state = reactive({
  isLoading: true,
  notFound: false,
  user: null,
  profile: null,
});

const fetchProfile = async () => {
  state.isLoading = true;

  try {
    const response = await axios.get(
      `/api/users/${route.params.id}/profile`
    );

    state.user = response.data.user;
    state.profile = response.data.profile;
  } catch (error) {
    if (error.response?.status === 404) {
      state.notFound = true;
    } else {
      console.error('Error loading public profile', error);
    }
  } finally {
    state.isLoading = false;
  }
};

onMounted(fetchProfile);
</script>

<template>
  <section class="mx-auto max-w-4xl px-6 py-10">

    <div
      v-if="state.isLoading"
      class="flex justify-center py-20"
    >
      <PulseLoader color="#4F46E5" />
    </div>

    <div
      v-else-if="state.notFound"
      class="rounded-2xl border border-slate-200 bg-white p-8 text-center"
    >
      <h1 class="text-2xl font-bold text-slate-900">
        Profile not found
      </h1>

      <p class="mt-2 text-slate-600">
        This user does not exist.
      </p>
    </div>

    <div
      v-else
      class="space-y-6"
    >
      <!-- Header -->
      <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 class="text-3xl font-bold text-slate-900">
          {{ state.user?.name }}
        </h1>

        <p
          v-if="state.profile?.headline"
          class="mt-2 text-lg font-medium text-indigo-600"
        >
          {{ state.profile.headline }}
        </p>

        <p
          v-if="state.profile?.location"
          class="mt-2 text-slate-600"
        >
          {{ state.profile.location }}
        </p>
      </div>

      <div
        v-if="!state.profile"
        class="rounded-2xl border border-slate-200 bg-white p-8"
      >
        <p class="text-slate-600">
          This user has not created a profile yet.
        </p>
      </div>

      <template v-else>

        <!-- Bio -->
        <div
          v-if="state.profile.bio"
          class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
        >
          <h2 class="text-xl font-bold text-slate-900">
            About
          </h2>

          <p class="mt-4 whitespace-pre-line text-slate-700">
            {{ state.profile.bio }}
          </p>
        </div>

        <!-- Job seeker fields -->
        <template v-if="state.user?.role === 'job_seeker'">

          <div
            v-if="state.profile.skills?.length"
            class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
          >
            <h2 class="text-xl font-bold text-slate-900">
              Skills
            </h2>

            <div class="mt-4 flex flex-wrap gap-2">
              <span
                v-for="skill in state.profile.skills"
                :key="skill"
                class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-700"
              >
                {{ skill }}
              </span>
            </div>
          </div>

          <div
            v-if="state.profile.experience"
            class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
          >
            <h2 class="text-xl font-bold text-slate-900">
              Experience
            </h2>

            <p class="mt-4 whitespace-pre-line text-slate-700">
              {{ state.profile.experience }}
            </p>
          </div>

          <div
            v-if="state.profile.resume_original_name"
            class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
          >
            <h2 class="text-xl font-bold text-slate-900">
              Resume
            </h2>

            <p class="mt-3 text-slate-700">
              Resume available:
              <span class="font-medium">
                {{ state.profile.resume_original_name }}
              </span>
            </p>
          </div>

        </template>

        <!-- Company fields -->
        <template v-if="state.user?.role === 'company'">

          <div
            v-if="state.profile.company_name || state.profile.website"
            class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
          >
            <h2 class="text-xl font-bold text-slate-900">
              Company
            </h2>

            <p
              v-if="state.profile.company_name"
              class="mt-4 text-slate-700"
            >
              {{ state.profile.company_name }}
            </p>

            <a
              v-if="state.profile.website"
              :href="state.profile.website"
              target="_blank"
              rel="noopener noreferrer"
              class="mt-3 inline-block font-medium text-indigo-600 hover:text-indigo-700"
            >
              Visit website
            </a>
          </div>

        </template>

      </template>
    </div>

  </section>
</template>