import { createRouter, createWebHistory } from 'vue-router';
import HomeView from '@/views/HomeView.vue';
import JobsView from '@/views/JobsView.vue';
import NotFoundView from '@/views/NotFoundView.vue';
import JobView from '@/views/JobView.vue';
import AddJobView from '@/views/AddJobView.vue';
import EditJobView from '@/views/EditJobView.vue';
import ApiTestView from '@/views/ApiTestView.vue';
import LoginView from '@/views/LoginView.vue';
import RegisterView from '@/views/RegisterView.vue';
import { useAuthStore } from '@/stores/auth';

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
    },
    {
      path: '/jobs',
      name: 'jobs',
      component: JobsView,
    },
    {
      path: '/jobs/:id',
      name: 'job',
      component: JobView,
    },
    {
      path: '/jobs/add',
      name: 'add-job',
      component: AddJobView,
    },
    {
      path: '/jobs/edit/:id',
      name: 'edit-job',
      component: EditJobView,
    },

    {
  path: '/connection-test',
  name: 'connection-test',
  component: ApiTestView,
},


    {
      path: '/:catchAll(.*)',
      name: 'not-found',
      component: NotFoundView,
    },

    {
  path: '/login',
  name: 'login',
  component: LoginView,
},

{
  path: '/register',
  name: 'register',
  component: RegisterView,
},


{
  path: '/jobs/add',
  name: 'add-job',
  component: AddJobView,

  meta: {
    requiresAuth:true,
    requiresRole:'company'
  },
},

{
  path: '/jobs/edit/:id',
  name: 'edit-job',
  component: EditJobView,

  meta: {
    requiresAuth:true,
  },
},


{
  path: "/applications",
  name:"applications",
  component: () => import("@/views/MyApplicationsView.vue"),

  meta: {
    requiresAuth:true,
    requiresRole:"job_seeker",
  },
},

{
  path: "/jobs/:id/applicants",
  name: "job-applicants",
  component: () => import("@/views/ApplicantsView.vue"),
  meta: {
    requiresAuth: true,
    requiresRole: "company",
  },
},


  ],
});


router.beforeEach(async (to) =>{   //("to") The route the user is trying to go to.
  const auth = useAuthStore();
  if(!auth.initialized){
    await auth.fetchUser();
  }
  if(to.meta.requiresAuth && !auth.isAuthenticated){
    return '/login';
  }


  if (
    to.meta.requiresRole &&
    auth.user?.role !== to.meta.requiresRole
  ) {
    return '/';
  }
});




export default router;
