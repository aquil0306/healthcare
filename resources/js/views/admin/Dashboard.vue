<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Dashboard</h1>
      </v-col>
    </v-row>
    
    <!-- Entity Stats -->
    <v-row>
      <v-col cols="12">
        <h2 class="text-h6 mb-2">Entities</h2>
      </v-col>
      <v-col cols="12" md="3" v-for="stat in entityStats" :key="stat.title">
        <v-card :to="stat.route" class="cursor-pointer" elevation="2">
          <v-card-text>
            <div class="text-h4 mb-1">{{ formatNumber(stat.value) }}</div>
            <div class="text-caption text-grey">{{ stat.title }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    
    <!-- Referral Stats -->
    <v-row>
      <v-col cols="12">
        <h2 class="text-h6 mb-2 mt-4">Referrals</h2>
      </v-col>
      <v-col cols="12" md="3" v-for="stat in referralStats" :key="stat.title">
        <v-card :to="{ name: 'admin.referrals' }" class="cursor-pointer" elevation="2">
          <v-card-text>
            <div class="text-h4 mb-1">{{ formatNumber(stat.value) }}</div>
            <div class="text-caption text-grey">{{ stat.title }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Key Performance Metrics -->
    <v-row>
      <v-col cols="12">
        <h2 class="text-h6 mb-2 mt-4">Performance Metrics</h2>
      </v-col>
      <v-col cols="12" md="3">
        <v-card elevation="2">
          <v-card-text>
            <div class="text-h4 mb-1">{{ formatPercentage(averageAiConfidence) }}%</div>
            <div class="text-caption text-grey">Average AI Confidence Score</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card elevation="2">
          <v-card-text>
            <div class="text-h4 mb-1" :class="getRateColor(escalationRate)">
              {{ formatPercentage(escalationRate) }}%
            </div>
            <div class="text-caption text-grey">Escalation Rate</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card elevation="2">
          <v-card-text>
            <div class="text-h4 mb-1" :class="getRateColor(cancellationRate)">
              {{ formatPercentage(cancellationRate) }}%
            </div>
            <div class="text-caption text-grey">Cancellation Rate</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card elevation="2">
          <v-card-text>
            <div class="text-h4 mb-1">{{ formatNumber(referralsPerDayCount) }}</div>
            <div class="text-caption text-grey">Referrals Per Day (Avg)</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Charts -->
    <v-row class="mt-4">
      <v-col cols="12" md="8">
        <v-card elevation="2">
          <v-card-title>Referrals Over Time</v-card-title>
          <v-card-text>
            <div v-if="loading" class="text-center pa-4">
              <v-progress-circular indeterminate color="primary"></v-progress-circular>
            </div>
            <div v-else style="height: 300px;">
              <Line :data="referralsChartData" :options="chartOptions" />
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="4">
        <v-card elevation="2">
          <v-card-title>Referrals by Urgency</v-card-title>
          <v-card-text>
            <div v-if="loading" class="text-center pa-4">
              <v-progress-circular indeterminate color="primary"></v-progress-circular>
            </div>
            <div v-else style="height: 300px;">
              <Doughnut :data="urgencyChartData" :options="doughnutOptions" />
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row class="mt-2">
      <v-col cols="12" md="6">
        <v-card elevation="2">
          <v-card-title>Referrals by Status</v-card-title>
          <v-card-text>
            <div v-if="loading" class="text-center pa-4">
              <v-progress-circular indeterminate color="primary"></v-progress-circular>
            </div>
            <div v-else style="height: 300px;">
              <Bar :data="statusChartData" :options="chartOptions" />
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="6">
        <v-card elevation="2">
          <v-card-title>Referrals by Department</v-card-title>
          <v-card-text>
            <div v-if="loading" class="text-center pa-4">
              <v-progress-circular indeterminate color="primary"></v-progress-circular>
            </div>
            <div v-else style="height: 300px;">
              <Bar :data="departmentChartData" :options="chartOptions" />
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { Line, Bar, Doughnut } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend
);

const loading = ref(true);
const entityStats = ref([
  { title: 'Total Hospitals', value: 0, route: { name: 'admin.hospitals' } },
  { title: 'Active Hospitals', value: 0, route: { name: 'admin.hospitals' } },
  { title: 'Total Patients', value: 0, route: { name: 'admin.patients' } },
  { title: 'Total Staff', value: 0, route: { name: 'admin.staff' } },
]);

const referralStats = ref([
  { title: 'Total Referrals', value: 0 },
  { title: 'Pending', value: 0 },
  { title: 'Emergency', value: 0 },
  { title: 'Completed', value: 0 },
]);

const referralsPerDay = ref([]);
const byUrgency = ref([]);
const byStatus = ref([]);
const byDepartment = ref([]);
const averageAiConfidence = ref(0);
const escalationRate = ref(0);
const cancellationRate = ref(0);

const formatNumber = (value) => {
  if (value === null || value === undefined || value === '-' || value === '') return '0';
  const num = typeof value === 'number' ? value : parseInt(value, 10);
  return isNaN(num) ? '0' : num.toLocaleString();
};

const formatPercentage = (value) => {
  if (value === null || value === undefined || isNaN(value)) return '0.0';
  return parseFloat(value).toFixed(1);
};

const getRateColor = (rate) => {
  if (rate === null || rate === undefined || isNaN(rate)) return '';
  const numRate = parseFloat(rate);
  if (numRate > 10) return 'text-error';
  if (numRate > 5) return 'text-warning';
  return 'text-success';
};

const referralsPerDayCount = computed(() => {
  if (!referralsPerDay.value || referralsPerDay.value.length === 0) return 0;
  const total = referralsPerDay.value.reduce((sum, item) => sum + (item.count || 0), 0);
  return Math.round(total / referralsPerDay.value.length);
});

const referralsChartData = computed(() => {
  const labels = referralsPerDay.value.map(item => {
    const date = new Date(item.date);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  });
  const data = referralsPerDay.value.map(item => item.count);

  return {
    labels,
    datasets: [
      {
        label: 'Referrals',
        data,
        borderColor: 'rgb(75, 192, 192)',
        backgroundColor: 'rgba(75, 192, 192, 0.2)',
        tension: 0.1,
      },
    ],
  };
});

const urgencyChartData = computed(() => {
  const labels = byUrgency.value.map(item => item.urgency.charAt(0).toUpperCase() + item.urgency.slice(1));
  const data = byUrgency.value.map(item => item.count);
  const colors = {
    emergency: 'rgba(255, 99, 132, 0.8)',
    urgent: 'rgba(255, 159, 64, 0.8)',
    routine: 'rgba(54, 162, 235, 0.8)',
  };

  return {
    labels,
    datasets: [
      {
        data,
        backgroundColor: byUrgency.value.map(item => colors[item.urgency] || 'rgba(201, 203, 207, 0.8)'),
      },
    ],
  };
});

const statusChartData = computed(() => {
  const labels = byStatus.value.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1));
  const data = byStatus.value.map(item => item.count);

  return {
    labels,
    datasets: [
      {
        label: 'Referrals',
        data,
        backgroundColor: 'rgba(54, 162, 235, 0.8)',
      },
    ],
  };
});

const departmentChartData = computed(() => {
  const labels = byDepartment.value.map(item => item.department || 'Unknown');
  const data = byDepartment.value.map(item => item.count);

  return {
    labels,
    datasets: [
      {
        label: 'Referrals',
        data,
        backgroundColor: 'rgba(153, 102, 255, 0.8)',
      },
    ],
  };
});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
    },
  },
};

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
    },
  },
};

onMounted(async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/v1/admin/reports/statistics');
    console.log('Statistics API Response:', response.data);
    
    if (!response.data.success) {
      console.error('API returned unsuccessful response:', response.data);
      return;
    }
    
    const data = response.data.data;
    console.log('Statistics Data:', data);
    
    if (!data) {
      console.error('No data in response');
      return;
    }
    
    // Ensure we have valid numbers
    const totalHospitals = parseInt(data.total_hospitals, 10) || 0;
    const activeHospitals = parseInt(data.active_hospitals, 10) || 0;
    const totalPatients = parseInt(data.total_patients, 10) || 0;
    const totalStaff = parseInt(data.total_staff, 10) || 0;
    const totalReferrals = parseInt(data.total_referrals, 10) || 0;
    
    const pendingCount = data.by_status?.find(s => s.status === 'submitted')?.count || 0;
    const emergencyCount = data.by_urgency?.find(u => u.urgency === 'emergency')?.count || 0;
    const completedCount = data.by_status?.find(s => s.status === 'completed')?.count || 0;
    
    entityStats.value = [
      { title: 'Total Hospitals', value: totalHospitals, route: { name: 'admin.hospitals' } },
      { title: 'Active Hospitals', value: activeHospitals, route: { name: 'admin.hospitals' } },
      { title: 'Total Patients', value: totalPatients, route: { name: 'admin.patients' } },
      { title: 'Total Staff', value: totalStaff, route: { name: 'admin.staff' } },
    ];
    
    referralStats.value = [
      { title: 'Total Referrals', value: totalReferrals },
      { title: 'Pending', value: parseInt(pendingCount, 10) || 0 },
      { title: 'Emergency', value: parseInt(emergencyCount, 10) || 0 },
      { title: 'Completed', value: parseInt(completedCount, 10) || 0 },
    ];

    referralsPerDay.value = Array.isArray(data.referrals_per_day) ? data.referrals_per_day : [];
    byUrgency.value = Array.isArray(data.by_urgency) ? data.by_urgency : [];
    byStatus.value = Array.isArray(data.by_status) ? data.by_status : [];
    byDepartment.value = Array.isArray(data.by_department) ? data.by_department : [];
    
    // Key performance metrics
    averageAiConfidence.value = data.average_ai_confidence 
      ? (parseFloat(data.average_ai_confidence) * 100) 
      : 0;
    escalationRate.value = data.escalation_rate || 0;
    cancellationRate.value = data.cancellation_rate || 0;
    
    console.log('Updated stats:', { 
      entityStats: entityStats.value, 
      referralStats: referralStats.value,
      averageAiConfidence: averageAiConfidence.value,
      escalationRate: escalationRate.value,
      cancellationRate: cancellationRate.value
    });
  } catch (error) {
    console.error('Failed to load statistics:', error);
    if (error.response) {
      console.error('Response error:', error.response.data);
      console.error('Status:', error.response.status);
    }
    alert('Failed to load dashboard statistics. Please check console for details.');
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
</style>

