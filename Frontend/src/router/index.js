import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    {
        path: '/',
        name: 'Home',
        component: () => import('../views/Home.vue'),
    },
    {
        path: '/catalogue',
        name: 'Catalogue',
        component: () => import('../views/Catalogue.vue'),
    },
     {
    path: '/profil',
    name: 'profile',
    component: () => import('../components/Profil.vue'),
    meta: { 
      title: 'Mon Profil',
      requiresAuth: true 
    }
  },
    
    {
        path: '/materiels/:id',
        name: 'MaterielDetail',
        component: () => import('../views/MaterielDetail.vue'),
        props: true,
    },
    {
        path: '/panier',
        name: 'Panier',
        component: () => import('../views/Panier.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/login',
        name: 'Login',
        component: () => import('../components/Login.vue'),
        meta: { guestOnly: true },
    },
     {
        path: '/footer',
        name: 'Footer',
        component: () => import('../components/Footer.vue'),
        meta: { guestOnly: true },
    },
    {
        path: '/register',
        name: 'Register',
        component: () => import('../components/Register.vue'),
        meta: { guestOnly: true },
    },
   
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Guard d'authentification
router.beforeEach((to, from, next) => {
    const isAuthenticated = localStorage.getItem('auth_token');
    
    if (to.meta.requiresAuth && !isAuthenticated) {
        next('/login');
    } else if (to.meta.guestOnly && isAuthenticated) {
        next('/dashboard');
    } else {
        next();
    }
});

export default router;