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
                <v-list-item v-if="item.user" @click="openRoleDialog(item)">
                  <template v-slot:prepend>
                    <v-icon>mdi-shield-account</v-icon>
                  </template>
                  <v-list-item-title>Assign Role</v-list-item-title>
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
            <v-text-field
              v-model="form.department"
              label="Department"
              :disabled="form.role === 'admin'"
            ></v-text-field>
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

    <!-- Assign Role Dialog -->
    <v-dialog v-model="roleDialog" max-width="500">
      <v-card>
        <v-card-title>Assign Role</v-card-title>
        <v-card-text>
          <v-select
            v-model="selectedRoleId"
            label="Select Role"
            :items="allRoles"
            item-title="name"
            item-value="id"
            return-object
          ></v-select>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="roleDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="assignRole">Assign</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const staff = ref([]);
const allRoles = ref([]);
const loading = ref(false);
const dialog = ref(false);
const roleDialog = ref(false);
const editing = ref(false);
const selectedStaff = ref(null);
const selectedRoleId = ref(null);
const form = ref({
  id: null,
  name: '',
  email: '',
  role: 'doctor',
  department: '',
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
    password: '',
    is_available: true,
  };
};

const openDialog = (staffMember = null) => {
  // Reset form first
  resetForm();
  
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

const openRoleDialog = (staffMember) => {
  selectedStaff.value = staffMember;
  selectedRoleId.value = null;
  roleDialog.value = true;
};

const assignRole = async () => {
  if (!selectedRoleId.value || !selectedStaff.value?.user) return;
  try {
    await axios.post(`/api/v1/admin/users/${selectedStaff.value.user.id}/assign-role`, {
      role_id: selectedRoleId.value.id || selectedRoleId.value,
    });
    roleDialog.value = false;
    loadStaff();
  } catch (error) {
    console.error('Failed to assign role:', error);
    alert(error.response?.data?.message || 'Failed to assign role');
  }
};

const loadRoles = async () => {
  try {
    const response = await axios.get('/api/v1/admin/roles', { params: { per_page: 1000 } });
    // API Resource collection returns: { data: [...], success: true, links: {...}, meta: {...} }
    if (response.data.data && Array.isArray(response.data.data)) {
      allRoles.value = response.data.data;
    } else if (response.data.data?.data && Array.isArray(response.data.data.data)) {
      // Paginated response structure
      allRoles.value = response.data.data.data;
    } else {
      allRoles.value = [];
    }
  } catch (error) {
    console.error('Failed to load roles:', error);
    allRoles.value = [];
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

onMounted(() => {
  loadStaff();
  loadRoles();
});
</script>

