<script setup>

import { onMounted , reactive } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import PulseLoader from 'vue-spinner/src/PulseLoader.vue';
import { useAuthStore } from '@/stores/auth';

const toast = useToast();
const auth = useAuthStore();
const state = reactive({
    isLoading: true,
    isSaving: false,
    isUploadingResume: false,


    resumeFile: null,
    resumeName: '',
   

    profile:{
        headline: '',
        bio: '',
        location: '',

        skillsText: '',
        experience: '',

        company_name: '',
        website: '',
    },
});

const fetchProfile = async () => {
  state.isLoading = true;

  try {
    const response = await axios.get('/api/profile');

    if (response.data.profile) {
      const profile = response.data.profile;
      state.resumeName = profile.resume_original_name ?? '';

      state.profile.bio = profile.bio ?? '';
      state.profile.location = profile.location ?? '';

      if (auth.user?.role === 'job_seeker') {
        state.profile.headline = profile.headline ?? '';
        state.profile.experience = profile.experience ?? '';

        state.profile.skillsText = Array.isArray(profile.skills)
          ? profile.skills.join(', ')
          : '';
      }

      if (auth.user?.role === 'company') {
        state.profile.company_name = profile.company_name ?? '';
        state.profile.website = profile.website ?? '';
      }
    }
  } catch (error) {
    console.error('Error fetching profile', error);
  } finally {
    state.isLoading = false;
  }
};


const handleResumeChange = (event) => {
  state.resumeFile = event.target.files[0] ?? null;
};


const uploadResume = async () => {
  if(!state.resumeFile){
    toast.error('Please choose a PDF first');
    return;
  }

  state.isUploadingResume = true;

  try{
    await axios.get('/sanctum/csrf-cookie');

    const formData = new FormData();

    formData.append('resume', state.resumeFile);

    const response = await axios.post('/api/profile/resume' , formData);

    state.resumeFile = null;
    state.resumeName = response.data.resume_original_name;

    toast.success('Resume uploaded successfully');

  }catch(error){
    if(error.response?.status == 422){
      toast.error('Please upload a valid PDF under 5 MB.');
    }
    else if(error.response?.status === 403){
      toast.error('Only job seekers can upload resumes');
    }else{
       toast.error('Could not upload resume.');
    }

    console.error('Error uploading resume', error);
  }finally{
    state.isUploadingResume = false;
  }
}



onMounted(fetchProfile);

const saveProfile = async () => {
  state.isSaving = true;

  try {
    await axios.get('/sanctum/csrf-cookie');

    const payload = {
      bio: state.profile.bio,
      location: state.profile.location,
    };

    if (auth.user?.role === 'job_seeker') {
      payload.headline = state.profile.headline;
      payload.experience = state.profile.experience;

      payload.skills = state.profile.skillsText
        .split(',')
        .map(skill => skill.trim())
        .filter(skill => skill);
    }

    if (auth.user?.role === 'company') {
      payload.company_name = state.profile.company_name;
      payload.website = state.profile.website;
    }

    const response = await axios.patch('/api/profile', payload);

    toast.success('Profile saved successfully.');
  } catch (error) {
    if (error.response?.status === 422) {
      toast.error('Please check your profile information.');
    } else {
      toast.error('Could not save profile.');
    }

   console.error('Error saving profile:', error.response?.status);
console.error('Backend response:', error.response?.data);
  } finally {
    state.isSaving = false;
  }
};


const downloadResume = async () => { 
  try{
    const response = await axios.get('/api/profile/resume' , { responseType: 'blob',});

    const url = window.URL.createObjectURL(response.data);

    const link = document.createElement('a');

    link.href = url;
    link.download = state.resumeName || 'resume.pdf';

    document.body.appendChild(link);

    link.click();
    link.remove();

    window.URL.revokeObjectURL(url);

  }catch(error){
    toast.error('Could not download resume');
    console.error('Error downloading resume ' , error);
  }



};

const deleteResume = async () => {
  try {
    await axios.get('/sanctum/csrf-cookie');

    await axios.delete('/api/profile/resume');

    state.resumeName = '';
    state.resumeFile = null;

    toast.success('Resume deleted successfully.');
  } catch (error) {
    if (error.response?.status === 404) {
      toast.error('No resume found.');
    } else {
      toast.error('Could not delete resume.');
    }

    console.error('Error deleting resume', error);
  }
};



</script>




<template>
  <section class="mx-auto max-w-3xl px-6 py-10">

    <div class="mb-8">
      <h1 class="text-3xl font-bold text-slate-900">
        My Profile
      </h1>

      <p class="mt-2 text-slate-600">
        Keep your profile information up to date.
      </p>
    </div>

    <div
      v-if="state.isLoading"
      class="flex justify-center py-20"
    >
      <PulseLoader color="#4F46E5" />
    </div>

    <form
      v-else
      @submit.prevent="saveProfile"
      class="space-y-6 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
    >
      <!-- Job seeker: headline -->
      <div v-if="auth.user?.role === 'job_seeker'">
        <label
          for="headline"
          class="mb-2 block font-semibold text-slate-700"
        >
          Headline
        </label>

        <input
          id="headline"
          v-model="state.profile.headline"
          type="text"
          placeholder="Junior Full Stack Developer"
          class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-500"
        />
      </div>

      <!-- Common: location -->
      <div>
        <label
          for="location"
          class="mb-2 block font-semibold text-slate-700"
        >
          Location
        </label>

        <input
          id="location"
          v-model="state.profile.location"
          type="text"
          placeholder="Beirut"
          class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-500"
        />
      </div>

      <!-- Common: bio -->
      <div>
        <label
          for="bio"
          class="mb-2 block font-semibold text-slate-700"
        >
          Bio
        </label>

        <textarea
          id="bio"
          v-model="state.profile.bio"
          rows="6"
          placeholder="Tell people about yourself..."
          class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-500"
        ></textarea>
      </div>

      <!-- Job seeker: skills -->
      <div v-if="auth.user?.role === 'job_seeker'">
        <label
          for="skills"
          class="mb-2 block font-semibold text-slate-700"
        >
          Skills
        </label>

        <input
          id="skills"
          v-model="state.profile.skillsText"
          type="text"
          placeholder="Laravel, Vue, MySQL"
          class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-500"
        />

        <p class="mt-2 text-sm text-slate-500">
          Separate skills with commas.
        </p>
      </div>

      <!-- Job seeker: experience -->
      <div v-if="auth.user?.role === 'job_seeker'">
        <label
          for="experience"
          class="mb-2 block font-semibold text-slate-700"
        >
          Experience
        </label>

        <textarea
          id="experience"
          v-model="state.profile.experience"
          rows="6"
          placeholder="Describe your experience..."
          class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-500"
        ></textarea>
      </div>

      <!-- Company: company name -->
      <div v-if="auth.user?.role === 'company'">
        <label
          for="company_name"
          class="mb-2 block font-semibold text-slate-700"
        >
          Company Name
        </label>

        <input
          id="company_name"
          v-model="state.profile.company_name"
          type="text"
          placeholder="CareerHub Technologies"
          class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-500"
        />
      </div>

      <!-- Company: website -->
      <div v-if="auth.user?.role === 'company'">
        <label
          for="website"
          class="mb-2 block font-semibold text-slate-700"
        >
          Website
        </label>

        <input
          id="website"
          v-model="state.profile.website"
          type="url"
          placeholder="https://example.com"
          class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-500"
        />
      </div>


      <div
  v-if="auth.user?.role === 'job_seeker'"
  class="border-t border-slate-200 pt-6"
>
  <label
    for="resume"
    class="mb-2 block font-semibold text-slate-700"
  >
    Resume / CV
  </label>

  <input
    id="resume"
    type="file"
    accept=".pdf,application/pdf"
    @change="handleResumeChange"
    class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-700"
  />

  <p class="mt-2 text-sm text-slate-500">
    PDF only, maximum 5 MB.
  </p>

  <p
    v-if="state.resumeName"
    class="mt-3 text-sm font-medium text-slate-700"
  >
    Current resume: {{ state.resumeName }}
  </p>

  <div
  v-if="state.resumeName"
  class="mt-4 flex gap-3"
>
  <button
    type="button"
    @click="downloadResume"
    class="rounded-xl border border-slate-300 bg-white px-5 py-2 font-semibold text-slate-700 transition hover:border-indigo-300 hover:text-indigo-600"
  >
    Download Resume
  </button>

  <button
    type="button"
    @click="deleteResume"
    class="rounded-xl border border-red-300 bg-white px-5 py-2 font-semibold text-red-600 transition hover:bg-red-50"
  >
    Delete Resume
  </button>
</div>


  <button
    type="button"
    @click="uploadResume"
    :disabled="state.isUploadingResume || !state.resumeFile"
    class="mt-4 rounded-xl border border-indigo-600 px-5 py-2 font-semibold text-indigo-600 transition hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-50"
  >
    {{ state.isUploadingResume ? 'Uploading...' : 'Upload Resume' }}
  </button>
</div>

      <!-- Save -->
      <button
        type="submit"
        :disabled="state.isSaving"
        class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
      >
        {{ state.isSaving ? 'Saving...' : 'Save Profile' }}
      </button>
    </form>

  </section>
</template>