<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <div class="d-flex justify-space-between align-center mb-4">
          <h1 class="text-h4">Patients</h1>
          <v-btn color="primary" @click="openDialog()">
            <v-icon start>mdi-plus</v-icon>
            Add Patient
          </v-btn>
        </div>
        <v-text-field
          v-model="search"
          label="Search"
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          class="mb-4"
          @input="loadPatients"
        ></v-text-field>
        <v-data-table
          :headers="headers"
          :items="patients"
          :loading="loading"
          @click:row="onRowClickPatient"
        >
          <template v-slot:item.actions="{ item }">
            <v-menu>
              <template v-slot:activator="{ props }">
                <v-btn icon="mdi-dots-vertical" size="small" v-bind="props" @click.stop></v-btn>
              </template>
              <v-list>
                <v-list-item @click="editPatient(item)">
                  <template v-slot:prepend>
                    <v-icon>mdi-pencil</v-icon>
                  </template>
                  <v-list-item-title>Edit</v-list-item-title>
                </v-list-item>
                <v-list-item @click="deletePatient(item)">
                  <template v-slot:prepend>
                    <v-icon color="error">mdi-delete</v-icon>
                  </template>
                  <v-list-item-title class="text-error">Delete</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>
          </template>
        </v-data-table>
      </v-col>
    </v-row>

    <!-- Add/Edit Dialog -->
    <v-dialog v-model="dialog" max-width="600">
      <v-card>
        <v-card-title>{{ editing ? 'Edit Patient' : 'Add Patient' }}</v-card-title>
        <v-card-text>
          <v-form>
            <v-text-field
              v-model="form.first_name"
              label="First Name"
              :rules="[rules.required]"
              required
            ></v-text-field>
            <v-text-field
              v-model="form.last_name"
              label="Last Name"
              :rules="[rules.required]"
              required
            ></v-text-field>
            <v-text-field
              v-model="form.date_of_birth"
              label="Date of Birth"
              type="date"
              :rules="[rules.required]"
              required
            ></v-text-field>
            <v-text-field
              v-model="form.national_id"
              label="National ID"
              :rules="[rules.required]"
              required
            ></v-text-field>
            <v-text-field
              v-model="form.insurance_number"
              label="Insurance Number"
              :rules="[rules.required]"
              required
            ></v-text-field>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="dialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="savePatient">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const patients = ref([]);
const loading = ref(false);
const dialog = ref(false);
const editing = ref(false);
const search = ref('');
const form = ref({
  id: null,
  first_name: '',
  last_name: '',
  date_of_birth: '',
  national_id: '',
  insurance_number: '',
});
const rules = {
  required: (v) => !!v || 'Required',
};

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'First Name', key: 'first_name' },
  { title: 'Last Name', key: 'last_name' },
  { title: 'Date of Birth', key: 'date_of_birth' },
  { title: 'National ID', key: 'national_id' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const resetForm = () => {
  form.value = {
    id: null,
    first_name: '',
    last_name: '',
    date_of_birth: '',
    national_id: '',
    insurance_number: '',
  };
};

const openDialog = (patient = null) => {
  // Reset form first
  resetForm();
  
  if (patient) {
    editing.value = true;
    // Extract only the fields needed for the form
    // Use date_of_birth from API (should be in Y-m-d format from PatientResource)
    const dob = patient.date_of_birth || '';
    // Use Object.assign to ensure reactivity
    Object.assign(form.value, {
      id: patient.id,
      first_name: patient.first_name || '',
      last_name: patient.last_name || '',
      date_of_birth: dob.includes('T') ? dob.split('T')[0] : (dob || ''),
      national_id: patient.national_id || '',
      insurance_number: patient.insurance_number || '',
    });
  } else {
    editing.value = false;
  }
  dialog.value = true;
};

const editPatient = (patient) => {
  openDialog(patient);
};

// Vuetify v-data-table row click passes (event, { item, index, internalItem })
const onRowClickPatient = (event, payload) => {
  if (payload && payload.item) {
    editPatient(payload.item);
  }
};

const savePatient = async () => {
  try {
    if (editing.value) {
      await axios.put(`/api/v1/admin/patients/${form.value.id}`, form.value);
    } else {
      await axios.post('/api/v1/admin/patients', form.value);
    }
    dialog.value = false;
    loadPatients();
  } catch (error) {
    console.error('Failed to save patient:', error);
    alert(error.response?.data?.message || 'Failed to save patient');
  }
};

const deletePatient = async (patient) => {
  if (!confirm(`Are you sure you want to delete ${patient.first_name} ${patient.last_name}?`)) return;
  try {
    await axios.delete(`/api/v1/admin/patients/${patient.id}`);
    loadPatients();
  } catch (error) {
    console.error('Failed to delete patient:', error);
    alert(error.response?.data?.message || 'Failed to delete patient');
  }
};

const loadPatients = async () => {
  loading.value = true;
  try {
    const params = search.value ? { search: search.value } : {};
    const response = await axios.get('/api/v1/admin/patients', { params });
    // API Resource collection returns: { data: [...], success: true, links: {...}, meta: {...} }
    if (response.data.data && Array.isArray(response.data.data)) {
      patients.value = response.data.data;
    } else if (response.data.data?.data && Array.isArray(response.data.data.data)) {
      // Paginated response structure
      patients.value = response.data.data.data;
    } else {
      patients.value = [];
    }
  } catch (error) {
    console.error('Failed to load patients:', error);
    patients.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadPatients();
});
</script>

