<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <div class="d-flex justify-space-between align-center mb-4">
          <h1 class="text-h4">Staff</h1>
          <v-btn color="primary" @click="openDialog()">
            <v-icon start>mdi-plus</v-icon>
            Add Staff
          </v-btn>
        </div>
        <v-data-table
          :headers="headers"
          :items="staff"
          :loading="loading"
          @click:row="onRowClickStaff"
        >
          <template v-slot:item.is_available="{ item }">
            <v-chip :color="item.is_available ? 'success' : 'error'" small>
              {{ item.is_available ? 'Available' : 'Unavailable' }}
            </v-chip>
          </template>
          <template v-slot:item.user="{ item }">
            <div v-if="item.user">
              <v-chip
                v-for="role in item.user.roles"
                :key="role.id"
                size="small"
                color="primary"
                class="ma-1"
              >
                {{ role.name }}
              </v-chip>
            </div>
          </template>
          <template v-slot:item.actions="{ item }">
            <v-menu>
              <template v-slot:activator="{ props }">
                <v-btn icon="mdi-dots-vertical" size="small" v-bind="props" @click.stop></v-btn>
              </template>
              <v-list>
                <v-list-item @click="editStaff(item)">
                  <template v-slot:prepend>
                    <v-icon>mdi-pencil</v-icon>
                  </template>
                  <v-list-item-title>Edit</v-list-item-title>
                </v-list-item>
                <v-list-item @click="deleteStaff(item)">
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
        <v-card-title>{{ editing ? 'Edit Staff' : 'Add Staff' }}</v-card-title>
        <v-card-text>
          <v-form>
            <v-text-field
              v-model="form.name"
              label="Name"
              :rules="[rules.required]"
              required
            ></v-text-field>
            <v-text-field
              v-model="form.email"
              label="Email"
              type="email"
              :rules="[rules.required, rules.email]"
              required
            ></v-text-field>
            <v-select
              v-model="form.role"
              label="Role"
              :items="['admin', 'doctor', 'coordinator']"
              :rules="[rules.required]"
              required
            ></v-select>
            <v-select
              v-model="form.department_id"
              label="Department"
              :items="departments"
              item-title="name"
              item-value="id"
              :disabled="form.role === 'admin'"
              clearable
              :rules="form.role === 'admin' ? [] : []"
              :loading="loadingDepartments"
            >
              <template v-slot:item="{ props, item }">
                <v-list-item 
                  v-bind="props"
                  v-if="item && (item.raw || item)"
                >
                  <v-list-item-title>{{ (item.raw && item.raw.name) || item.name || 'Unknown' }}</v-list-item-title>
                  <v-list-item-subtitle v-if="(item.raw && item.raw.code) || item.code">
                    {{ (item.raw && item.raw.code) || item.code }}
                  </v-list-item-subtitle>
                </v-list-item>
              </template>
            </v-select>
            <v-text-field
              v-if="!editing"
              v-model="form.password"
              label="Password"
              type="password"
              :rules="editing ? [] : [rules.required, rules.minLength]"
              :required="!editing"
            ></v-text-field>
            <v-text-field
              v-if="editing"
              v-model="form.password"
              label="New Password (leave blank to keep current)"
              type="password"
              :rules="form.password ? [rules.minLength] : []"
            ></v-text-field>
            <v-switch
              v-model="form.is_available"
              label="Available"
            ></v-switch>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="dialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="saveStaff">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const staff = ref([]);
const departments = ref([]);
const loading = ref(false);
const loadingDepartments = ref(false);
const dialog = ref(false);
const editing = ref(false);
const form = ref({
  id: null,
  name: '',
  email: '',
  role: 'doctor',
  department: '',
  department_id: null,
  password: '',
  is_available: true,
});
const rules = {
  required: (v) => !!v || 'Required',
  email: (v) => !v || /.+@.+\..+/.test(v) || 'Email must be valid',
  minLength: (v) => !v || v.length >= 8 || 'Password must be at least 8 characters',
};

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Name', key: 'name' },
  { title: 'Email', key: 'email' },
  { title: 'Role', key: 'user' },
  { title: 'Department', key: 'department' },
  { title: 'Available', key: 'is_available' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const resetForm = () => {
  form.value = {
    id: null,
    name: '',
    email: '',
    role: 'doctor',
    department: '',
    department_id: null,
    password: '',
    is_available: true,
  };
};

const openDialog = async (staffMember = null) => {
  // Reset form first
  resetForm();
  
  // Ensure departments are loaded before opening dialog
  if (departments.value.length === 0) {
    await loadDepartments();
  }
  
  if (staffMember) {
    editing.value = true;
    // Extract only the fields needed for the form
    // Use Object.assign to ensure reactivity
    Object.assign(form.value, {
      id: staffMember.id,
      name: staffMember.name || '',
      email: staffMember.email || '',
      role: staffMember.role || 'doctor',
      department: staffMember.department || '',
      department_id: staffMember.department_id || staffMember.department_data?.id || null,
      password: '', // Never populate password
      is_available: staffMember.is_available !== undefined ? staffMember.is_available : true,
    });
  } else {
    editing.value = false;
  }
  dialog.value = true;
};

const editStaff = (staffMember) => {
  openDialog(staffMember);
};

// Vuetify v-data-table row click passes (event, { item, index, internalItem })
const onRowClickStaff = (event, payload) => {
  if (payload && payload.item) {
    editStaff(payload.item);
  }
};

const saveStaff = async () => {
  try {
    const data = { ...form.value };
    if (editing.value && !data.password) {
      delete data.password;
    }
    // Remove department string if department_id is provided
    if (data.department_id) {
      delete data.department;
    }
    if (editing.value) {
      await axios.put(`/api/v1/admin/staff/${data.id}`, data);
    } else {
      await axios.post('/api/v1/admin/staff', data);
    }
    dialog.value = false;
    loadStaff();
  } catch (error) {
    console.error('Failed to save staff:', error);
    alert(error.response?.data?.message || 'Failed to save staff');
  }
};

const deleteStaff = async (staffMember) => {
  if (!confirm(`Are you sure you want to delete ${staffMember.name}?`)) return;
  try {
    await axios.delete(`/api/v1/admin/staff/${staffMember.id}`);
    loadStaff();
  } catch (error) {
    console.error('Failed to delete staff:', error);
    alert(error.response?.data?.message || 'Failed to delete staff');
  }
};

const loadStaff = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/v1/admin/staff');
    // API Resource collection returns: { data: [...], success: true, links: {...}, meta: {...} }
    if (response.data.data && Array.isArray(response.data.data)) {
      staff.value = response.data.data;
    } else if (response.data.data?.data && Array.isArray(response.data.data.data)) {
      // Paginated response structure
      staff.value = response.data.data.data;
    } else {
      staff.value = [];
    }
  } catch (error) {
    console.error('Failed to load staff:', error);
    staff.value = [];
  } finally {
    loading.value = false;
  }
};

const loadDepartments = async () => {
  loadingDepartments.value = true;
  try {
    const response = await axios.get('/api/v1/admin/departments', { params: { per_page: 1000, is_active: true } });
    if (response.data.data && Array.isArray(response.data.data)) {
      departments.value = response.data.data;
    } else if (response.data.data?.data && Array.isArray(response.data.data.data)) {
      departments.value = response.data.data.data;
    } else {
      departments.value = [];
    }
  } catch (error) {
    console.error('Failed to load departments:', error);
    departments.value = [];
  } finally {
    loadingDepartments.value = false;
  }
};

onMounted(() => {
  loadStaff();
  loadDepartments();
});
</script>

