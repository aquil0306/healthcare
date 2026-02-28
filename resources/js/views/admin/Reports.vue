<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <div class="d-flex justify-space-between align-center mb-4">
          <h1 class="text-h4">Reports & Statistics</h1>
          <div class="d-flex gap-2">
            <v-btn
              color="primary"
              prepend-icon="mdi-download"
              @click="downloadCSV"
              :disabled="loading"
            >
              Download CSV
            </v-btn>
            <v-btn
              color="secondary"
              prepend-icon="mdi-file-pdf-box"
              @click="downloadPDF"
              :disabled="loading"
            >
              Download PDF
            </v-btn>
          </div>
        </div>

        <!-- Date Range Filter -->
        <v-card class="mb-4" elevation="2">
          <v-card-text>
            <v-row>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="dateFrom"
                  label="Date From"
                  type="date"
                  density="compact"
                  variant="outlined"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="dateTo"
                  label="Date To"
                  type="date"
                  density="compact"
                  variant="outlined"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="4" class="d-flex align-center">
                <v-btn
                  color="primary"
                  @click="loadStatistics"
                  :loading="loading"
                  block
                >
                  Apply Filter
                </v-btn>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- Key Metrics Cards -->
        <v-row class="mb-4">
          <v-col cols="12" md="3">
            <v-card elevation="2">
              <v-card-text>
                <div class="text-h4 mb-1">{{ formatNumber(stats?.total_referrals || 0) }}</div>
                <div class="text-caption text-grey">Total Referrals</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="3">
            <v-card elevation="2">
              <v-card-text>
                <div class="text-h4 mb-1">{{ formatPercentage(stats?.average_ai_confidence || 0) }}%</div>
                <div class="text-caption text-grey">Average AI Confidence</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="3">
            <v-card elevation="2">
              <v-card-text>
                <div class="text-h4 mb-1" :class="getRateColor(stats?.escalation_rate || 0)">
                  {{ formatPercentage(stats?.escalation_rate || 0) }}%
                </div>
                <div class="text-caption text-grey">Escalation Rate</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="3">
            <v-card elevation="2">
              <v-card-text>
                <div class="text-h4 mb-1" :class="getRateColor(stats?.cancellation_rate || 0)">
                  {{ formatPercentage(stats?.cancellation_rate || 0) }}%
                </div>
                <div class="text-caption text-grey">Cancellation Rate</div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <!-- Referrals Per Day Table -->
        <v-card class="mb-4" elevation="2">
          <v-card-title>Referrals Per Day</v-card-title>
          <v-card-text>
            <v-data-table
              :headers="referralsPerDayHeaders"
              :items="referralsPerDay"
              :loading="loading"
              :items-per-page="15"
              class="elevation-0"
            >
              <template v-slot:item.date="{ item }">
                <div>{{ formatDate(item.date) }}</div>
                <div class="text-caption text-grey" v-if="isToday(item.date)">Today</div>
              </template>
              <template v-slot:item.count="{ item }">
                <v-chip size="small" color="primary">{{ item.count }}</v-chip>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>

        <!-- Statistics Tables -->
        <v-row>
          <!-- By Urgency -->
          <v-col cols="12" md="6">
            <v-card elevation="2">
              <v-card-title>Referrals by Urgency</v-card-title>
              <v-card-text>
                <v-data-table
                  :headers="urgencyHeaders"
                  :items="byUrgency"
                  :loading="loading"
                  hide-default-footer
                  class="elevation-0"
                >
                  <template v-slot:item.urgency="{ item }">
                    <v-chip
                      :color="getUrgencyColor(item.urgency)"
                      size="small"
                      variant="flat"
                    >
                      {{ item.urgency.toUpperCase() }}
                    </v-chip>
                  </template>
                  <template v-slot:item.count="{ item }">
                    <strong>{{ formatNumber(item.count) }}</strong>
                  </template>
                </v-data-table>
              </v-card-text>
            </v-card>
          </v-col>

          <!-- By Status -->
          <v-col cols="12" md="6">
            <v-card elevation="2">
              <v-card-title>Referrals by Status</v-card-title>
              <v-card-text>
                <v-data-table
                  :headers="statusHeaders"
                  :items="byStatus"
                  :loading="loading"
                  hide-default-footer
                  class="elevation-0"
                >
                  <template v-slot:item.status="{ item }">
                    <v-chip
                      :color="getStatusColor(item.status)"
                      size="small"
                      variant="flat"
                    >
                      {{ item.status.toUpperCase() }}
                    </v-chip>
                  </template>
                  <template v-slot:item.count="{ item }">
                    <strong>{{ formatNumber(item.count) }}</strong>
                  </template>
                </v-data-table>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <!-- By Department -->
        <v-row class="mt-2">
          <v-col cols="12">
            <v-card elevation="2">
              <v-card-title>Referrals by Department</v-card-title>
              <v-card-text>
                <v-data-table
                  :headers="departmentHeaders"
                  :items="byDepartment"
                  :loading="loading"
                  :items-per-page="10"
                  class="elevation-0"
                >
                  <template v-slot:item.department="{ item }">
                    {{ item.department || 'Unknown' }}
                  </template>
                  <template v-slot:item.count="{ item }">
                    <strong>{{ formatNumber(item.count) }}</strong>
                  </template>
                </v-data-table>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const stats = ref(null);
const loading = ref(false);
const dateFrom = ref(new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]);
const dateTo = ref(new Date().toISOString().split('T')[0]);

const referralsPerDay = ref([]);
const byUrgency = ref([]);
const byStatus = ref([]);
const byDepartment = ref([]);

const referralsPerDayHeaders = [
  { title: 'Date', key: 'date', sortable: true },
  { title: 'Count', key: 'count', sortable: true },
];

const urgencyHeaders = [
  { title: 'Urgency', key: 'urgency' },
  { title: 'Count', key: 'count' },
];

const statusHeaders = [
  { title: 'Status', key: 'status' },
  { title: 'Count', key: 'count' },
];

const departmentHeaders = [
  { title: 'Department', key: 'department' },
  { title: 'Count', key: 'count' },
];

const formatNumber = (value) => {
  if (value === null || value === undefined || value === '-' || value === '') return '0';
  const num = typeof value === 'number' ? value : parseInt(value, 10);
  return isNaN(num) ? '0' : num.toLocaleString();
};

const formatPercentage = (value) => {
  if (value === null || value === undefined || isNaN(value)) return '0.0';
  // If value is already a percentage (0-100), return as is
  // If value is a decimal (0-1), multiply by 100
  const num = parseFloat(value);
  return num > 1 ? num.toFixed(1) : (num * 100).toFixed(1);
};

const formatDate = (dateString) => {
  if (!dateString) return '-';
  try {
    // Handle both YYYY-MM-DD format and full datetime strings
    const date = new Date(dateString + 'T00:00:00');
    if (isNaN(date.getTime())) {
      return dateString;
    }
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
  } catch (e) {
    return dateString;
  }
};

const isToday = (dateString) => {
  if (!dateString) return false;
  try {
    const date = new Date(dateString + 'T00:00:00');
    const today = new Date();
    return date.toDateString() === today.toDateString();
  } catch (e) {
    return false;
  }
};

const getRateColor = (rate) => {
  if (rate === null || rate === undefined || isNaN(rate)) return '';
  const numRate = parseFloat(rate);
  if (numRate > 10) return 'text-error';
  if (numRate > 5) return 'text-warning';
  return 'text-success';
};

const getUrgencyColor = (urgency) => {
  const colors = { emergency: 'red', urgent: 'orange', routine: 'blue' };
  return colors[urgency] || 'grey';
};

const getStatusColor = (status) => {
  const colors = {
    submitted: 'grey',
    triaged: 'blue',
    assigned: 'purple',
    acknowledged: 'green',
    in_progress: 'info',
    completed: 'success',
    cancelled: 'red',
  };
  return colors[status] || 'grey';
};

const loadStatistics = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams({
      date_from: dateFrom.value,
      date_to: dateTo.value,
    });
    
    const response = await axios.get(`/api/v1/admin/reports/statistics?${params}`);
    
    if (response.data.success && response.data.data) {
      const data = response.data.data;
      stats.value = data;
      
      referralsPerDay.value = Array.isArray(data.referrals_per_day) ? data.referrals_per_day : [];
      byUrgency.value = Array.isArray(data.by_urgency) ? data.by_urgency : [];
      
      // Aggregate by_status to handle any duplicates (case-insensitive)
      const statusMap = new Map();
      if (Array.isArray(data.by_status)) {
        data.by_status.forEach(item => {
          const key = item.status?.toLowerCase().trim() || '';
          if (key) {
            const existing = statusMap.get(key);
            if (existing) {
              existing.count += parseInt(item.count || 0, 10);
            } else {
              statusMap.set(key, {
                status: key,
                count: parseInt(item.count || 0, 10),
              });
            }
          }
        });
      }
      byStatus.value = Array.from(statusMap.values());
      
      byDepartment.value = Array.isArray(data.by_department) ? data.by_department : [];
      
      // Fix average_ai_confidence if it's a decimal (0-1) instead of percentage
      if (data.average_ai_confidence !== null && data.average_ai_confidence !== undefined) {
        const conf = parseFloat(data.average_ai_confidence);
        if (conf <= 1) {
          stats.value.average_ai_confidence = conf * 100;
        }
      }
    }
  } catch (error) {
    console.error('Failed to load reports:', error);
    alert('Failed to load statistics. Please check console for details.');
  } finally {
    loading.value = false;
  }
};

const downloadCSV = () => {
  if (!stats.value) {
    alert('Please load statistics first');
    return;
  }

  let csv = 'Healthcare Referral Management - Statistics Report\n';
  csv += `Generated: ${new Date().toLocaleString()}\n`;
  csv += `Date Range: ${dateFrom.value} to ${dateTo.value}\n\n`;

  // Key Metrics
  csv += 'Key Metrics\n';
  csv += `Total Referrals,${stats.value.total_referrals || 0}\n`;
  csv += `Average AI Confidence,${formatPercentage(stats.value.average_ai_confidence || 0)}%\n`;
  csv += `Escalation Rate,${formatPercentage(stats.value.escalation_rate || 0)}%\n`;
  csv += `Cancellation Rate,${formatPercentage(stats.value.cancellation_rate || 0)}%\n\n`;

  // Referrals Per Day
  csv += 'Referrals Per Day\n';
  csv += 'Date,Count\n';
  referralsPerDay.value.forEach(item => {
    csv += `${item.date},${item.count}\n`;
  });
  csv += '\n';

  // By Urgency
  csv += 'Referrals by Urgency\n';
  csv += 'Urgency,Count\n';
  byUrgency.value.forEach(item => {
    csv += `${item.urgency},${item.count}\n`;
  });
  csv += '\n';

  // By Status
  csv += 'Referrals by Status\n';
  csv += 'Status,Count\n';
  byStatus.value.forEach(item => {
    csv += `${item.status},${item.count}\n`;
  });
  csv += '\n';

  // By Department
  csv += 'Referrals by Department\n';
  csv += 'Department,Count\n';
  byDepartment.value.forEach(item => {
    csv += `${item.department || 'Unknown'},${item.count}\n`;
  });

  // Create download
  const blob = new Blob([csv], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `referral-statistics-${dateFrom.value}-to-${dateTo.value}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  window.URL.revokeObjectURL(url);
};

const downloadPDF = () => {
  // For PDF, we'll create a simple HTML page and use browser print
  if (!stats.value) {
    alert('Please load statistics first');
    return;
  }

  let html = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Referral Statistics Report</title>
      <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h1 { color: #1976d2; }
        h2 { color: #424242; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #1976d2; color: white; }
        .metric { margin: 10px 0; }
        .metric-label { font-weight: bold; }
      </style>
    </head>
    <body>
      <h1>Healthcare Referral Management - Statistics Report</h1>
      <p><strong>Generated:</strong> ${new Date().toLocaleString()}</p>
      <p><strong>Date Range:</strong> ${dateFrom.value} to ${dateTo.value}</p>
      
      <h2>Key Metrics</h2>
      <div class="metric">
        <span class="metric-label">Total Referrals:</span> ${stats.value.total_referrals || 0}
      </div>
      <div class="metric">
        <span class="metric-label">Average AI Confidence:</span> ${formatPercentage(stats.value.average_ai_confidence || 0)}%
      </div>
      <div class="metric">
        <span class="metric-label">Escalation Rate:</span> ${formatPercentage(stats.value.escalation_rate || 0)}%
      </div>
      <div class="metric">
        <span class="metric-label">Cancellation Rate:</span> ${formatPercentage(stats.value.cancellation_rate || 0)}%
      </div>
      
      <h2>Referrals Per Day</h2>
      <table>
        <tr><th>Date</th><th>Count</th></tr>
  `;
  
  referralsPerDay.value.forEach(item => {
    html += `<tr><td>${formatDate(item.date)}</td><td>${item.count}</td></tr>`;
  });
  
  html += `
      </table>
      
      <h2>Referrals by Urgency</h2>
      <table>
        <tr><th>Urgency</th><th>Count</th></tr>
  `;
  
  byUrgency.value.forEach(item => {
    html += `<tr><td>${item.urgency.toUpperCase()}</td><td>${item.count}</td></tr>`;
  });
  
  html += `
      </table>
      
      <h2>Referrals by Status</h2>
      <table>
        <tr><th>Status</th><th>Count</th></tr>
  `;
  
  byStatus.value.forEach(item => {
    html += `<tr><td>${item.status.toUpperCase()}</td><td>${item.count}</td></tr>`;
  });
  
  html += `
      </table>
      
      <h2>Referrals by Department</h2>
      <table>
        <tr><th>Department</th><th>Count</th></tr>
  `;
  
  byDepartment.value.forEach(item => {
    html += `<tr><td>${item.department || 'Unknown'}</td><td>${item.count}</td></tr>`;
  });
  
  html += `
      </table>
    </body>
    </html>
  `;

  const printWindow = window.open('', '_blank');
  printWindow.document.write(html);
  printWindow.document.close();
  printWindow.focus();
  setTimeout(() => {
    printWindow.print();
  }, 250);
};

onMounted(() => {
  loadStatistics();
});
</script>

<style scoped>
.gap-2 {
  gap: 0.5rem;
}
</style>
