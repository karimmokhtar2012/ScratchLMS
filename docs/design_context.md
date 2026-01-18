# AI Graphic Designer Context: ScratchLMS Design System

## 1. Design Philosophy

- **Theme**: Ultra-Modern Dark Mode, clean, content-focused.
- **Style**: "Bento" grid aesthetics, high contrast, subtle borders, and glassmorphism.
- **Vibe**: Professional, tech-forward, sleek, and responsive.

## 2. Color Palette

The application uses a customized Tailwind color palette rooted in **Zinc** for neutrals and **Indigo/Violet** for accents.

### Core Backgrounds

- **Global Background**: `#0a0a0a` (Deepest Black) - Used on `<body>` and main containers.
- **Surface**: `#18181b` (Zinc-900) - Used for sidebars, dropdowns, and secondary areas.
- **Card Background**: `#27272a` (Zinc-800) - Used for cards and panels.

### Accents & Gradients

- **Primary Gradient**: `bg-gradient-to-br from-indigo-500 to-violet-600` (Used for Logos, Primary Buttons, Highlights).
- **Primary Solid**: `#3b82f6` (Blue-500) - Standard links or focus rings.
- **Hover/Interaction**: Indigo-400 (Text hover), Violet-500/10 (Background hover).

### Status Colors

- **Success**: Emerald (e.g., `text-emerald-400`, `bg-emerald-500/10`).
- **Warning/Pending**: Amber (e.g., `text-amber-300`, `bg-amber-500/10`).
- **Error/Danger**: Red (e.g., `text-red-400`, `bg-red-500/10`).
- **Text**: `#e2e8f0` (Zinc-200) for body text, Zinc-400/500 for muted text.

## 3. Typography

- **Primary Font**: `Rubik` (Google Fonts).
- **Secondary/Custom**: `RB` (Custom local font: RB REGULAR/BOLD/LIGHT.TTF).
- **Weights**:
  - Headings: Bold (700) or ExtraBold (800).
  - Body: Regular (400) or Light (300).
- **Special**: `.gradient-text` uses linear gradient (Violet to Blue) for marketing headings.
- **RTL Support**: Arabic uses 'RB' font exclusively.

## 4. Component Styles

### Cards (`.bento-card`)

A signature element of the design.

- **Classes**: `bg-card rounded-2xl p-6 border border-zinc-700/50 shadow-lg hover:border-zinc-600 transition duration-300`
- **Visuals**: Dark grey card, very subtle semi-transparent border, soft shadow. On hover, the border lightens slightly.

### Buttons & Actions

- **Primary Dashboard**: Gradient backgrounds (`from-indigo-500 to-violet-600`), `rounded-lg` or `rounded-xl`.
- **Primary Marketing**: High-level CTAs (e.g. "Get Started") use `rounded-full` for a friendlier, consumer-facing look.
- **Secondary/Ghost**: `bg-zinc-900 border border-zinc-800 text-zinc-400 hover:text-white hover:border-zinc-600`.
- **Icons**: FontAwesome 6 (`fa-solid`, `fa-regular`) is used exclusively.

### Form Elements (Inputs)

- **Style**: `bg-zinc-900 border border-zinc-800 text-zinc-300 rounded-xl py-2 px-4`.
- **Focus**: `focus:outline-none focus:border-indigo-500`.
- **Placeholder**: Zinc-500.

### Navigation (Sidebar)

- **Background**: `bg-black border-r border-zinc-800`.
- **Links**: `rounded-xl hover:bg-zinc-900 text-zinc-400 hover:text-white`.
- **Active State**: Typically distinguished by brighter text or a slight background tint (though logical state styling logic resides in PHP).

## 5. Effects & Animations

### Transitions

- **Page Load**: Custom JavaScript transition.
  - Entry: `opacity: 0; transform: translateY(8px)` -> `opacity: 1; transform: none`.
  - Exit: `opacity: 0; transform: translateY(8px)`.
  - Duration: 320ms ease.

### Glassmorphism

- Used in Sticky Headers (`bg-black/80 backdrop-blur-xl`) and Flash Messages (`backdrop-blur-sm`).
- Borders often use `white/10` or `zinc-700/50` to enhance the glass effect.

### Loaders

- **Conic Gradient Spin**: A custom CSS `conic-gradient` used for spinners (`.page-loader`, `.modern-loader`) with colors `rgba(139, 92, 246)` (Violet).

## 6. Layout Structure

- **Dashboard**: Fixed Sidebar (Left) + Scrollable Main Content (Right).
- **Public**: Fixed Transparent Header (Glass) + Full width content.
- **Mobile**: Sidebar becomes an off-canvas drawer; Header appears with hamburger menu.

## 7. Directives for AI Generation

When generating new UI mockups or code:

1. **Always** use `class="dark"` on the `html` tag logic.
2. **Avoid** pure white backgrounds; default to `#0a0a0a` or `#18181b`.
3. **Use** `rounded-xl` or `rounded-2xl` for containers; `rounded-lg` for dashboard buttons; `rounded-full` for marketing buttons.
4. **Implement** thin, subtle borders (`border-zinc-800` or `border-zinc-700/50`) to define hierarchy instead of heavy drop shadows.
5. **Utilize** the Indigo-to-Violet gradient for key "call to action" elements to maintain brand identity.
