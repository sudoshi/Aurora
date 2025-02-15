/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.jsx",
  ],
  safelist: [
    // ICU/Critical (Red)
    'bg-red-900/50',
    'bg-red-900/30',
    'bg-red-600',
    'bg-red-700',
    'border-red-500/50',
    'text-red-400',
    'ring-red-500',
    // Surgical (Purple)
    'bg-purple-900/50',
    'bg-purple-900/30',
    'bg-purple-600',
    'bg-purple-700',
    'border-purple-500/50',
    'text-purple-400',
    'ring-purple-500',
    // Oncology (Blue)
    'bg-blue-900/50',
    'bg-blue-900/30',
    'bg-blue-600',
    'bg-blue-700',
    'border-blue-500/50',
    'text-blue-400',
    'ring-blue-500',
    // Neurology (Teal)
    'bg-teal-900/50',
    'bg-teal-900/30',
    'bg-teal-600',
    'bg-teal-700',
    'border-teal-500/50',
    'text-teal-400',
    'ring-teal-500',
    // Admin (Gray)
    'bg-gray-900/50',
    'bg-gray-900/30',
    'bg-gray-600',
    'bg-gray-700',
    'border-gray-500/50',
    'text-gray-400',
    'ring-gray-500',
    // Multidisciplinary (Green)
    'bg-green-900/50',
    'bg-green-900/30',
    'bg-green-600',
    'bg-green-700',
    'border-green-500/50',
    'text-green-400',
    'ring-green-500',
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#0EA5E9',
          50: '#F0F9FF',
          100: '#E0F2FE',
          200: '#BAE6FD',
          300: '#7DD3FC',
          400: '#38BDF8',
          500: '#0EA5E9',
          600: '#0284C7',
          700: '#0369A1',
          800: '#075985',
          900: '#0C4A6E',
        },
      },
    },
  },
  plugins: [],
}
