<template>
  <div class="login-container">
    <!-- Animated Background -->
    <div class="login-background">
      <div class="gradient-overlay"></div>
      <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
      </div>
    </div>

    <!-- Login Card -->
    <v-container class="fill-height" fluid>
      <v-row align="center" justify="center" class="login-row">
        <v-col cols="12" sm="10" md="8" lg="5" xl="4">
          <v-card 
            class="login-card" 
            elevation="24"
            :class="{ 'shake': hasError }"
          >
            <!-- Card Header with Logo/Icon -->
            <div class="login-header">
              <div class="logo-container">
                <v-icon size="64" color="white" class="logo-icon">mdi-heart-pulse</v-icon>
              </div>
              <h1 class="login-title">Healthcare Referral Management</h1>
              <p class="login-subtitle">Secure access to patient referral system</p>
            </div>

            <v-card-text class="pa-8">
              <v-form @submit.prevent="handleLogin" ref="loginForm">
                <!-- Email Field -->
                <v-text-field
                  v-model="email"
                  label="Email Address"
                  type="email"
                  required
                  prepend-inner-icon="mdi-email-outline"
                  variant="outlined"
                  color="primary"
                  class="mb-4"
                  :rules="emailRules"
                  :error-messages="emailError"
                  @input="clearError"
                  autofocus
                >
                  <template v-slot:prepend-inner>
                    <v-icon color="primary">mdi-email-outline</v-icon>
                  </template>
                </v-text-field>

                <!-- Password Field -->
                <v-text-field
                  v-model="password"
                  label="Password"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  variant="outlined"
                  color="primary"
                  class="mb-2"
                  :rules="passwordRules"
                  :error-messages="passwordError"
                  @input="clearError"
                >
                  <template v-slot:prepend-inner>
                    <v-icon color="primary">mdi-lock-outline</v-icon>
                  </template>
                  <template v-slot:append-inner>
                    <v-btn
                      icon
                      variant="text"
                      size="small"
                      @click="showPassword = !showPassword"
                    >
                      <v-icon>{{ showPassword ? 'mdi-eye-off' : 'mdi-eye' }}</v-icon>
                    </v-btn>
                  </template>
                </v-text-field>

                <!-- Remember Me & Forgot Password -->
                <div class="d-flex justify-space-between align-center mb-6">
                  <v-checkbox
                    v-model="rememberMe"
                    label="Remember me"
                    color="primary"
                    density="compact"
                    hide-details
                  ></v-checkbox>
                  <a href="#" class="forgot-password" @click.prevent="handleForgotPassword">
                    Forgot Password?
                  </a>
                </div>

                <!-- Error Alert -->
                <v-alert
                  v-if="error"
                  type="error"
                  variant="tonal"
                  class="mb-4"
                  closable
                  @click:close="error = ''"
                  :class="{ 'fade-in': error }"
                >
                  <v-icon start>mdi-alert-circle</v-icon>
                  {{ error }}
                </v-alert>

                <!-- Login Button -->
                <v-btn
                  type="submit"
                  color="primary"
                  size="x-large"
                  block
                  class="login-button"
                  :loading="loading"
                  :disabled="loading"
                  elevation="4"
                >
                  <template v-if="!loading">
                    <v-icon start>mdi-login</v-icon>
                    Sign In
                  </template>
                  <template v-else>
                    <span class="mr-2">Signing in...</span>
                  </template>
                </v-btn>

                <!-- Divider -->
                <v-divider class="my-6">
                  <span class="px-4 text-grey">or</span>
                </v-divider>

                <!-- Demo Credentials Info -->
                <v-alert
                  type="info"
                  variant="tonal"
                  density="compact"
                  class="demo-info"
                  border="start"
                  border-color="primary"
                >
                  <template v-slot:prepend>
                    <v-icon color="primary">mdi-information-outline</v-icon>
                  </template>
                  <div class="text-caption">
                    <strong>Demo Access:</strong><br>
                    <span class="text-primary">Admin:</span> admin@healthcare.com / password<br>
                    <span class="text-primary">Staff:</span> doctor.cardiology@healthcare.com / password
                  </div>
                </v-alert>
              </v-form>
            </v-card-text>

            <!-- Footer -->
            <v-card-actions class="pa-4 bg-grey-lighten-5">
              <v-spacer></v-spacer>
              <div class="text-center">
                <v-icon size="16" color="primary" class="mr-1">mdi-shield-check</v-icon>
                <span class="text-caption text-grey">
                  Secure Healthcare Platform © 2024
                </span>
              </div>
              <v-spacer></v-spacer>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);
const showPassword = ref(false);
const rememberMe = ref(false);
const hasError = ref(false);
const emailError = ref('');
const passwordError = ref('');
const authStore = useAuthStore();
const router = useRouter();
const loginForm = ref(null);

// Validation rules
const emailRules = [
  v => !!v || 'Email is required',
  v => /.+@.+\..+/.test(v) || 'Email must be valid',
];

const passwordRules = [
  v => !!v || 'Password is required',
  v => (v && v.length >= 6) || 'Password must be at least 6 characters',
];

const clearError = () => {
  if (error.value) {
    error.value = '';
    hasError.value = false;
  }
  emailError.value = '';
  passwordError.value = '';
};

const handleLogin = async () => {
  // Validate form
  const { valid } = await loginForm.value.validate();
  if (!valid) {
    return;
  }

  loading.value = true;
  error.value = '';
  hasError.value = false;
  emailError.value = '';
  passwordError.value = '';

  try {
    const result = await authStore.login({ 
      email: email.value, 
      password: password.value 
    });
    
    if (result.success) {
      // Determine redirect based on user role
      const userRole = authStore.user?.staff?.role;
      if (userRole === 'admin') {
        router.push({ name: 'admin.dashboard' });
      } else if (['doctor', 'coordinator'].includes(userRole)) {
        router.push({ name: 'staff.referrals' });
      } else {
        router.push({ name: 'admin.dashboard' });
      }
    } else {
      error.value = result.error || 'Invalid credentials. Please try again.';
      hasError.value = true;
      setTimeout(() => {
        hasError.value = false;
      }, 500);
    }
  } catch (err) {
    error.value = 'An unexpected error occurred. Please try again.';
    hasError.value = true;
  } finally {
    loading.value = false;
  }
};

const handleForgotPassword = () => {
  // TODO: Implement forgot password functionality
  alert('Forgot password functionality coming soon!');
};
</script>

<style scoped>
.login-container {
  position: relative;
  min-height: 100vh;
  overflow: hidden;
}

.login-background {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 0;
  background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 25%, #90caf9 50%, #64b5f6 75%, #42a5f5 100%);
  background-size: 400% 400%;
  animation: gradientShift 20s ease infinite;
}

@keyframes gradientShift {
  0% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0% 50%;
  }
}

.gradient-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
}

.floating-shapes {
  position: absolute;
  width: 100%;
  height: 100%;
  overflow: hidden;
}

.shape {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  animation: float 20s infinite ease-in-out;
}

.shape-1 {
  width: 300px;
  height: 300px;
  top: -150px;
  left: -150px;
  animation-delay: 0s;
}

.shape-2 {
  width: 200px;
  height: 200px;
  bottom: -100px;
  right: -100px;
  animation-delay: 5s;
}

.shape-3 {
  width: 150px;
  height: 150px;
  top: 50%;
  right: 10%;
  animation-delay: 10s;
}

@keyframes float {
  0%, 100% {
    transform: translate(0, 0) rotate(0deg);
  }
  33% {
    transform: translate(30px, -30px) rotate(120deg);
  }
  66% {
    transform: translate(-20px, 20px) rotate(240deg);
  }
}

.login-row {
  position: relative;
  z-index: 1;
  min-height: 100vh;
}

.login-card {
  border-radius: 24px !important;
  overflow: hidden;
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.95) !important;
  transition: all 0.3s ease;
}

.login-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2) !important;
}

.login-card.shake {
  animation: shake 0.5s;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
  20%, 40%, 60%, 80% { transform: translateX(10px); }
}

.login-header {
  text-align: center;
  padding: 2rem 2rem 1rem;
  background: linear-gradient(135deg, #0d47a1 0%, #1565c0 50%, #0277bd 100%);
  color: white;
  position: relative;
  overflow: hidden;
}

.login-header::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
  opacity: 0.3;
}

.logo-container {
  margin-bottom: 1rem;
}

.logo-icon {
  background: rgba(255, 255, 255, 0.25);
  border-radius: 50%;
  padding: 1.25rem;
  backdrop-filter: blur(10px);
  animation: pulse 2s infinite;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.05);
    opacity: 0.9;
  }
}

.login-title {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: white;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  position: relative;
  z-index: 1;
}

.login-subtitle {
  font-size: 0.9rem;
  opacity: 0.95;
  color: white;
  position: relative;
  z-index: 1;
  font-weight: 400;
}

.login-button {
  height: 56px !important;
  font-size: 1.1rem !important;
  font-weight: 600;
  letter-spacing: 0.5px;
  text-transform: none;
  border-radius: 12px;
  transition: all 0.3s ease;
}

.login-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(21, 101, 192, 0.4) !important;
}

.forgot-password {
  color: #1565c0;
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s ease;
}

.forgot-password:hover {
  color: #0d47a1;
  text-decoration: underline;
}

.demo-info {
  border-radius: 12px;
}

.fade-in {
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive adjustments */
@media (max-width: 600px) {
  .login-header {
    padding: 1.5rem 1rem 1rem;
  }
  
  .login-title {
    font-size: 1.5rem;
  }
  
  .v-card-text {
    padding: 1.5rem !important;
  }
}

/* Custom input styling */
:deep(.v-field--variant-outlined) {
  border-radius: 12px;
}

:deep(.v-field__input) {
  padding-top: 4px;
}

:deep(.v-btn--variant-text) {
  color: #1565c0;
}

/* Healthcare theme adjustments */
:deep(.v-field--variant-outlined .v-field__outline) {
  color: #1565c0;
}

:deep(.v-field--focused .v-field__outline) {
  color: #0d47a1;
  border-width: 2px;
}
</style>
