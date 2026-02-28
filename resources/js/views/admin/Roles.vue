<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <div class="d-flex justify-space-between align-center mb-4">
          <h1 class="text-h4">Roles</h1>
          <v-btn color="primary" @click="openDialog()">
            <v-icon start>mdi-plus</v-icon>
            Add Role
          </v-btn>
        </div>
        <v-data-table
          :headers="headers"
          :items="roles"
          :loading="loading"
          @click:row="(event, row) => editRole(row.item)"
        >
          <template v-slot:item.permissions="{ item }">
            <v-chip
              v-for="permission in item.permissions"
              :key="permission.id"
              size="small"
              class="ma-1"
            >
              {{ permission.name }}
            </v-chip>
          </template>
          <template v-slot:item.users_count="{ item }">
            {{ item.users_count }} user(s)
          </template>
          <template v-slot:item.actions="{ item }">
            <v-menu>
              <template v-slot:activator="{ props }">
                <v-btn icon="mdi-dots-vertical" size="small" v-bind="props" @click.stop></v-btn>
              </template>
              <v-list>
                <v-list-item @click="editRole(item)">
                  <template v-slot:prepend>
                    <v-icon>mdi-pencil</v-icon>
                  </template>
                  <v-list-item-title>Edit</v-list-item-title>
                </v-list-item>
                <v-list-item @click="deleteRole(item)">
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
    <v-dialog v-model="dialog" max-width="800">
      <v-card>
        <v-card-title>{{ editing ? 'Edit Role' : 'Add Role' }}</v-card-title>
        <v-card-text>
          <v-form ref="formRef">
            <v-text-field
              v-model="form.name"
              label="Role Name"
              :rules="[rules.required]"
              required
              :disabled="loading"
            ></v-text-field>
            <v-divider class="my-4"></v-divider>
            <h3 class="mb-2">Permissions</h3>
            <v-row>
              <v-col
                v-for="permission in allPermissions"
                :key="permission.id"
                cols="12"
                md="4"
              >
                <v-checkbox
                  v-model="form.permissions"
                  :value="permission.id"
                  :label="permission.name"
                  hide-details
                ></v-checkbox>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="dialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="saveRole">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import axios from 'axios';

const roles = ref([]);
const allPermissions = ref([]);
const loading = ref(false);
const dialog = ref(false);
const editing = ref(false);
const formRef = ref(null);
const form = ref({
  id: null,
  name: '',
  permissions: [],
});
const rules = {
  required: (v) => !!v || 'Required',
};

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Name', key: 'name' },
  { title: 'Permissions', key: 'permissions' },
  { title: 'Users', key: 'users_count' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const openDialog = async (role = null) => {
  if (role) {
    editing.value = true;
    // Update form properties directly to maintain reactivity
    form.value.id = role.id;
    form.value.name = role.name || '';
    // Ensure permissions is an array and map to IDs
    form.value.permissions = (role.permissions || []).map((p) => p.id || p);
  } else {
    editing.value = false;
    form.value.id = null;
    form.value.name = '';
    form.value.permissions = [];
  }
  // Wait for next tick to ensure form is updated before opening dialog
  await nextTick();
  dialog.value = true;
};

const editRole = (role) => {
  // Handle both row click and menu click
  if (role && role.id) {
    openDialog(role);
  }
};

const saveRole = async () => {
  // Validate form
  if (formRef.value) {
    const { valid } = await formRef.value.validate();
    if (!valid) return;
  }

  if (!form.value.name) {
    alert('Role name is required');
    return;
  }

  try {
    loading.value = true;
    const data = {
      name: form.value.name,
      permissions: form.value.permissions || [],
    };
    if (editing.value && form.value.id) {
      await axios.put(`/api/v1/admin/roles/${form.value.id}`, data);
    } else {
      await axios.post('/api/v1/admin/roles', data);
    }
    dialog.value = false;
    // Reset form
    form.value = { id: null, name: '', permissions: [] };
    editing.value = false;
    await loadRoles();
  } catch (error) {
    console.error('Failed to save role:', error);
    alert(error.response?.data?.message || 'Failed to save role');
  } finally {
    loading.value = false;
  }
};

const deleteRole = async (role) => {
  if (!confirm(`Are you sure you want to delete role "${role.name}"?`)) return;
  try {
    await axios.delete(`/api/v1/admin/roles/${role.id}`);
    loadRoles();
  } catch (error) {
    console.error('Failed to delete role:', error);
    alert(error.response?.data?.message || 'Failed to delete role');
  }
};

const loadRoles = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/v1/admin/roles');
    if (response.data.success && response.data.data) {
      roles.value = response.data.data.data || response.data.data;
    } else {
      console.error('Unexpected response structure:', response.data);
      roles.value = [];
    }
  } catch (error) {
    console.error('Failed to load roles:', error);
    if (error.response) {
      console.error('Response error:', error.response.data);
      alert('Failed to load roles: ' + (error.response.data?.message || error.message));
    }
    roles.value = [];
  } finally {
    loading.value = false;
  }
};

const loadPermissions = async () => {
  try {
    const response = await axios.get('/api/v1/admin/permissions', { params: { per_page: 1000 } });
    if (response.data.success && response.data.data) {
      allPermissions.value = response.data.data.data || response.data.data;
    } else {
      console.error('Unexpected response structure:', response.data);
      allPermissions.value = [];
    }
  } catch (error) {
    console.error('Failed to load permissions:', error);
    if (error.response) {
      console.error('Response error:', error.response.data);
    }
    allPermissions.value = [];
  }
};

onMounted(() => {
  loadRoles();
  loadPermissions();
});
</script>

