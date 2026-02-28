import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import { aliases, mdi } from 'vuetify/iconsets/mdi';
import '@mdi/font/css/materialdesignicons.css';
import 'vuetify/styles';

export default createVuetify({
    components,
    directives,
    icons: {
        defaultSet: 'mdi',
        aliases,
        sets: {
            mdi,
        },
    },
    theme: {
        defaultTheme: 'light',
        themes: {
            light: {
                colors: {
                    primary: '#1565c0', // Healthcare blue
                    secondary: '#0d47a1', // Deep blue
                    accent: '#42a5f5', // Light blue accent
                    error: '#d32f2f', // Medical red
                    info: '#0288d1', // Info blue
                    success: '#388e3c', // Medical green
                    warning: '#f57c00', // Warning orange
                    background: '#e3f2fd', // Light blue background
                    surface: '#ffffff',
                },
            },
        },
    },
});

