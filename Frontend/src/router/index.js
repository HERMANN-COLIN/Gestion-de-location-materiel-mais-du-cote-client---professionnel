import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    // ==================== ROUTES PUBLIQUES ====================
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
        path: '/materiels/:id',
        name: 'MaterielDetail',
        component: () => import('../views/MaterielDetail.vue'),
        props: true,
    },
    {
        path: '/panier',
        name: 'Panier',
        component: () => import('../views/Panier.vue'),
    },
    {
        path: '/checkout',
        name: 'Checkout',
        component: () => import('../views/Checkout.vue'),
    },

    // ==================== ROUTES UTILISATEURS ====================
    {
        path: '/profil',
        name: 'Profil',
        component: () => import('../components/Profil.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/dashboard',
        name: 'Dashboard',
        component: () => import('../components/Dashboard.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/commandes/:id',
        name: 'DetailsCommande',
        component: () => import('../views/DetailsCommande.vue'),
        meta: { requiresAuth: true },
    },

    // ==================== ROUTES ADMINISTRATION ====================
    {
        path: '/admin/produits',
        name: 'AdminDashboard',
        component: () => import('../views/AdminDashboard.vue'),
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/admin/commandes',
        name: 'AdminCommandes',
        component: () => import('../views/AdminCommandes.vue'),
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/admin/statistiques',
        name: 'AdminStat',
        component: () => import('../views/AdminStat.vue'),
        meta: { requiresAuth: true, requiresAdmin: true },
    },

    // ==================== AUTHENTIFICATION ====================
    {
        path: '/login',
        name: 'Login',
        component: () => import('../components/Login.vue'),
        meta: { guestOnly: true },
    },
    {
        path: '/register',
        name: 'Register',
        component: () => import('../components/Register.vue'),
        meta: { guestOnly: true },
    },
    
    // Route Footer (si vous en avez besoin isolément)
    {
        path: '/footer',
        name: 'Footer',
        component: () => import('../components/Footer.vue'),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// ==================== GUARDS DE NAVIGATION ====================
router.beforeEach((to, from, next) => {
    const isAuthenticated = localStorage.getItem('token');
    let user = {};
    
    try {
        user = JSON.parse(localStorage.getItem('user') || '{}');
    } catch (e) {
        user = {};
    }

    // 1. Si la route nécessite une authentification et que l'utilisateur n'est pas connecté
    if (to.meta.requiresAuth && !isAuthenticated) {
        next('/login');
    } 
    // 2. Si la route nécessite un rôle Admin (type_id = 3)
    else if (to.meta.requiresAdmin && user.type_id !== 3) {
        next('/'); // Redirection vers l'accueil s'il n'est pas admin
    }
    // 3. Si la route est réservée aux invités (non connectés)
    else if (to.meta.guestOnly && isAuthenticated) {
        next('/dashboard');
    } 
    // 4. Sinon, on laisse passer
    else {
        next();
    }
});

export default router;