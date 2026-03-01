import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('../views/Login.vue'),
        meta: { guest: true },
    },
    {
        path: '/',
        redirect: '/dashboard',
        meta: { requiresAuth: true },
    },
    {
        path: '/dashboard',
        name: 'admin.dashboard',
        component: () => import('../views/admin/Dashboard.vue'),
        meta: { requiresAuth: true, role: 'admin' },
    },
    {
        path: '/admin/referrals',
        name: 'admin.referrals',
        component: () => import('../views/admin/Referrals.vue'),
        meta: { requiresAuth: true, role: 'admin' },
    },
    {
        path: '/admin/referrals/:id',
        name: 'admin.referral.show',
        component: () => import('../views/admin/ReferralDetail.vue'),
        meta: { requiresAuth: true, role: 'admin' },
    },
    {
        path: '/admin/reports',
        name: 'admin.reports',
        component: () => import('../views/admin/Reports.vue'),
        meta: { requiresAuth: true, role: 'admin' },
    },
    {
        path: '/admin/hospitals',
        name: 'admin.hospitals',
        component: () => import('../views/admin/Hospitals.vue'),
        meta: { requiresAuth: true, role: 'admin' },
    },
    {
        path: '/admin/patients',
        name: 'admin.patients',
        component: () => import('../views/admin/Patients.vue'),
        meta: { requiresAuth: true, role: 'admin' },
    },
    {
        path: '/admin/staff',
        name: 'admin.staff',
        component: () => import('../views/admin/Staff.vue'),
        meta: { requiresAuth: true, role: 'admin' },
    },
    {
        path: '/admin/roles',
        name: 'admin.roles',
        component: () => import('../views/admin/Roles.vue'),
        meta: { requiresAuth: true, role: 'admin' },
    },
    {
        path: '/admin/permissions',
        name: 'admin.permissions',
        component: () => import('../views/admin/Permissions.vue'),
        meta: { requiresAuth: true, role: 'admin' },
    },
    {
        path: '/admin/icd10-codes',
        name: 'admin.icd10-codes',
        component: () => import('../views/admin/Icd10Codes.vue'),
        meta: { requiresAuth: true, role: 'admin' },
    },
    {
        path: '/admin/departments',
        name: 'admin.departments',
        component: () => import('../views/admin/Departments.vue'),
        meta: { requiresAuth: true, role: 'admin' },
    },
    {
        path: '/admin/audit-logs',
        name: 'admin.audit-logs',
        component: () => import('../views/admin/AuditLogs.vue'),
        meta: { requiresAuth: true, role: 'admin' },
    },
    {
        path: '/admin/notifications',
        name: 'admin.notifications',
        component: () => import('../views/admin/Notifications.vue'),
        meta: { requiresAuth: true, role: 'admin' },
    },
    {
        path: '/staff/referrals',
        name: 'staff.referrals',
        component: () => import('../views/staff/Referrals.vue'),
        meta: { requiresAuth: true, role: ['doctor', 'coordinator'] },
    },
    {
        path: '/staff/referrals/:id',
        name: 'staff.referral.show',
        component: () => import('../views/staff/ReferralDetail.vue'),
        meta: { requiresAuth: true, role: ['doctor', 'coordinator'] },
    },
    {
        path: '/notifications',
        name: 'notifications',
        component: () => import('../views/Notifications.vue'),
        meta: { requiresAuth: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    // Re-hydrate user on hard refresh if we have a token but no user loaded yet
    if (authStore.token && !authStore.user) {
        try {
            await authStore.fetchUser();
        } catch (e) {
            // If fetchUser fails, clear token and treat as logged out
            authStore.token = null;
            authStore.user = null;
            localStorage.removeItem('token');
        }
    }

    // Prevent redirect loops - if already going to login, allow it
    if (to.name === 'login') {
        if (authStore.isAuthenticated) {
            next({ name: 'admin.dashboard' });
        } else {
            next();
        }
        return;
    }

    // Check authentication
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next({ name: 'login' });
        return;
    }

    // If guest route and authenticated, redirect to dashboard
    if (to.meta.guest && authStore.isAuthenticated) {
        next({ name: 'admin.dashboard' });
        return;
    }

    // Check role permissions
    if (to.meta.role) {
        const userRole = authStore.user?.staff?.role;
        const allowedRoles = Array.isArray(to.meta.role) ? to.meta.role : [to.meta.role];
        
        // If user has no role or role doesn't match, redirect to login
        if (!userRole || !allowedRoles.includes(userRole)) {
            next({ name: 'login' });
            return;
        }
    }

    next();
});

export default router;

