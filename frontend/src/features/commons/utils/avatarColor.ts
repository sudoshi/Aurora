/**
 * Deterministic avatar background color based on user ID.
 * Colors from the mockup wireframe — varied, not all teal.
 */
const AVATAR_COLORS = [
  "#0d9488", // teal (brand)
  "#2563eb", // blue
  "#7c3aed", // purple
  "#0891b2", // cyan
  "#059669", // emerald
  "#d97706", // amber
  "#dc2626", // red
  "#4f46e5", // indigo
];

export function avatarColor(userId: number): string {
  return AVATAR_COLORS[userId % AVATAR_COLORS.length];
}
