<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">My Referrals</h1>
        <v-data-table
          :headers="headers"
          :items="referrals"
          :loading="loading"
          @click:row="viewReferral"
        >
          <template v-slot:item.urgency="{ item }">
            <v-chip :color="getUrgencyColor(item.urgency)" size="small">
              {{ item.urgency }}
            </v-chip>
          </template>
          <template v-slot:item.status="{ item }">
            <v-chip :color="getStatusColor(item.status)" size="small">
              {{ item.status }}
            </v-chip>
          </template>
          <template v-slot:item.patient="{ item }">
            <div v-if="item.patient">
              <div class="font-weight-medium">{{ item.patient.first_name }} {{ item.patient.last_name }}</div>
              <div class="text-caption text-grey">DOB: {{ formatDate(item.patient.date_of_birth) }}</div>
            </div>
            <span v-else class="text-grey">-</span>
          </template>
          <template v-slot:item.hospital="{ item }">
            <div v-if="item.hospital">
              {{ item.hospital.name }}
            </div>
            <span v-else class="text-grey">-</span>
          </template>
          <template v-slot:item.department="{ item }">
            <div v-if="item.department_resource">
              {{ item.department_resource.name }}
            </div>
            <span v-else-if="item.department">{{ item.department }}</span>
            <span v-else class="text-grey">-</span>
          </template>
          <template v-slot:item.created_at="{ item }">
            {{ item.created_at }}
          </template>
          <template v-slot:item.actions="{ item }">
            <v-menu>
              <template v-slot:activator="{ props }">
                <v-btn
                  icon="mdi-dots-vertical"
                  size="small"
                  v-bind="props"
                  @click.stop
                ></v-btn>
              </template>
              <v-list>
                <v-list-item @click.stop="viewReferral(null, { item })">
                  <template v-slot:prepend>
                    <v-icon>mdi-eye</v-icon>
                  </template>
                  <v-list-item-title>View Details</v-list-item-title>
                </v-list-item>
                <v-list-item
                  v-if="item.status === 'assigned'"
                  @click.stop="acknowledge(item)"
                >
                  <template v-slot:prepend>
                    <v-icon color="primary">mdi-check-circle</v-icon>
                  </template>
                  <v-list-item-title>Acknowledge</v-list-item-title>
                </v-list-item>
                <v-list-item
                  v-if="item.status === 'acknowledged' || item.status === 'in_progress'"
                  @click.stop="updateStatus(item, 'in_progress')"
                >
                  <template v-slot:prepend>
                    <v-icon color="info">mdi-play-circle</v-icon>
                  </template>
                  <v-list-item-title>Mark as In Progress</v-list-item-title>
                </v-list-item>
                <v-list-item
                  v-if="item.status !== 'completed' && item.status !== 'cancelled'"
                  @click.stop="completeReferral(item)"
                >
                  <template v-slot:prepend>
                    <v-icon color="success">mdi-check-all</v-icon>
                  </template>
                  <v-list-item-title class="text-success">Mark as Complete</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>
          </template>
        </v-data-table>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const referrals = ref([]);
const loading = ref(false);

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Patient', key: 'patient' },
  { title: 'Hospital', key: 'hospital' },
  { title: 'Urgency', key: 'urgency' },
  { title: 'Status', key: 'status' },
  { title: 'Department', key: 'department' },
  { title: 'Created', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const getUrgencyColor = (urgency) => {
  const colors = {
    routine: 'grey',
    urgent: 'orange',
    emergency: 'red',
  };
  return colors[urgency] || 'grey';
};

const getStatusColor = (status) => {
  const colors = {
    submitted: 'blue',
    triaged: 'info',
    assigned: 'warning',
    acknowledged: 'primary',
    in_progress: 'info',
    completed: 'success',
    cancelled: 'error',
  };
  return colors[status] || 'grey';
};

const formatDate = (dateString) => {
  return dateString || '-';
};

const viewReferral = (event, { item }) => {
  if (item) {
    router.push({ name: 'staff.referral.show', params: { id: item.id } });
  }
};

const acknowledge = async (item) => {
  try {
    await axios.post(`/api/v1/staff/referrals/${item.id}/acknowledge`);
    await loadReferrals();
  } catch (error) {
    console.error('Failed to acknowledge:', error);
    alert(error.response?.data?.message || 'Failed to acknowledge referral');
  }
};

const updateStatus = async (item, status) => {
  try {
    await axios.post(`/api/v1/staff/referrals/${item.id}/update-status`, { status });
    await loadReferrals();
  } catch (error) {
    console.error('Failed to update status:', error);
    alert(error.response?.data?.message || 'Failed to update referral status');
  }
};

const completeReferral = async (item) => {
  if (!confirm(`Are you sure you want to mark referral #${item.id} as complete?`)) {
    return;
  }
  
  try {
    await axios.post(`/api/v1/staff/referrals/${item.id}/complete`);
    await loadReferrals();
  } catch (error) {
    console.error('Failed to complete referral:', error);
    alert(error.response?.data?.message || 'Failed to mark referral as complete');
  }
};

const loadReferrals = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/v1/staff/referrals');
    if (response.data.success && response.data.data) {
      referrals.value = Array.isArray(response.data.data) 
        ? response.data.data 
        : [];
    } else {
      referrals.value = [];
    }
  } catch (error) {
    console.error('Failed to load referrals:', error);
    referrals.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(loadReferrals);
</script>
