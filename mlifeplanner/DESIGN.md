---
name: Organic Precision (Dark Edition)
colors:
  surface: '#131315'
  surface-dim: '#131315'
  surface-bright: '#39393b'
  surface-container-lowest: '#0e0e10'
  surface-container-low: '#1b1b1d'
  surface-container: '#201f21'
  surface-container-high: '#2a2a2c'
  surface-container-highest: '#353437'
  on-surface: '#e5e1e4'
  on-surface-variant: '#bbcabf'
  inverse-surface: '#e5e1e4'
  inverse-on-surface: '#313032'
  outline: '#86948a'
  outline-variant: '#3c4a42'
  surface-tint: '#4edea3'
  primary: '#4edea3'
  on-primary: '#003824'
  primary-container: '#10b981'
  on-primary-container: '#00422b'
  inverse-primary: '#006c49'
  secondary: '#ffb95f'
  on-secondary: '#472a00'
  secondary-container: '#ee9800'
  on-secondary-container: '#5b3800'
  tertiary: '#ffb3b6'
  on-tertiary: '#68001a'
  tertiary-container: '#ff7884'
  on-tertiary-container: '#78001f'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#6ffbbe'
  primary-fixed-dim: '#4edea3'
  on-primary-fixed: '#002113'
  on-primary-fixed-variant: '#005236'
  secondary-fixed: '#ffddb8'
  secondary-fixed-dim: '#ffb95f'
  on-secondary-fixed: '#2a1700'
  on-secondary-fixed-variant: '#653e00'
  tertiary-fixed: '#ffdada'
  tertiary-fixed-dim: '#ffb3b6'
  on-tertiary-fixed: '#40000c'
  on-tertiary-fixed-variant: '#920028'
  background: '#131315'
  on-background: '#e5e1e4'
  surface-variant: '#353437'
typography:
  display-lg:
    fontFamily: DM Serif Display
    fontSize: 48px
    fontWeight: '400'
    lineHeight: 56px
    letterSpacing: -0.02em
  display-lg-mobile:
    fontFamily: DM Serif Display
    fontSize: 36px
    fontWeight: '400'
    lineHeight: 42px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: DM Serif Display
    fontSize: 32px
    fontWeight: '400'
    lineHeight: 40px
  title-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-sm:
    fontFamily: DM Mono
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
    letterSpacing: 0.05em
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  unit: 4px
  gutter: 24px
  margin-mobile: 16px
  margin-desktop: 64px
  container-max: 1280px
---

## Brand & Style
This design system represents a fusion of high-end editorial aesthetics and technical rigor. It targets a sophisticated audience that values precision, depth, and a premium "pro-tool" feel. The atmosphere is quiet, focused, and authoritative.

The style is **Minimalist-Technocratic**. It leverages high-quality typography and a disciplined grid, but introduces depth through subtle tonal layering and razor-sharp borders rather than heavy shadows. The shift to a dark mode palette transforms the brand from an airy editorial experience into a powerful, immersive environment reminiscent of high-end audio hardware or advanced financial terminals.

## Colors
The color palette is anchored in a deep charcoal base to reduce eye strain and provide a canvas for high-contrast accents.

- **Backgrounds**: The core canvas uses a near-black (#0D0D0F). 
- **Surfaces**: Interactive containers and cards use a slightly elevated shade (#1A1A1C) to create visual separation without relying on shadows.
- **Accents**: 
    - **Emerald (Primary)**: Used for success states, active indicators, and primary actions.
    - **Glowing Gold (Secondary)**: Reserved for premium features, warnings, or highlighted data points.
    - **Ruby (Tertiary)**: High-visibility error states and critical destructive actions.
- **Borders**: Defined by a low-contrast grey (#2D2D30) to maintain the "precision" feel without breaking the dark aesthetic.

## Typography
The typography system relies on a hierarchy of three distinct typefaces to communicate intent.

- **Serif (DM Serif Display)**: Used for large headlines and editorial moments. It provides the "Organic" soul of the design.
- **Sans (Plus Jakarta Sans)**: The workhorse for UI components, navigation, and body copy. It is modern, legible, and friendly.
- **Mono (DM Mono)**: Used for metadata, labels, and data points to reinforce the "Precision" aspect of the brand.

All text should be rendered with `antialiased` smoothing. In dark mode, ensure body text maintains a slight off-white (#F9FAFB) to prevent vibrating against the black background.

## Layout & Spacing
The layout follows a **Fixed Grid** philosophy on desktop and a **Fluid** approach on mobile. 

- **Grid**: A 12-column system is used for desktop (1280px max-width). Gutters are fixed at 24px to ensure a spacious, high-end feel.
- **Rhythm**: Spacing is based on a 4px baseline unit. Most component spacing should use multiples of 4 (e.g., 16px, 24px, 32px).
- **Mobile**: Margins shrink to 16px. Typography scales down slightly, and multi-column layouts collapse into a single-column stack.

## Elevation & Depth
Depth is expressed through **Tonal Layers** and **Low-Contrast Outlines**.

1.  **Level 0 (Base)**: #0D0D0F - The main application background.
2.  **Level 1 (Card/Surface)**: #1A1A1C - Used for cards, sidebars, and navigation headers.
3.  **Level 2 (Overlay)**: #242426 - Used for modals, popovers, and tooltips.

Instead of heavy shadows, use a 1px solid border (#2D2D30) to define edges. For high-priority elements like active modals, a very subtle, large-radius ambient shadow (0px 20px 40px rgba(0,0,0,0.5)) can be used to lift the element off the page.

## Shapes
The shape language is **Soft (0.25rem)**. This provides a subtle nod to organic forms without sacrificing the professional, architectural feel of the grid.

- **Small Components**: Checkboxes, input fields, and tags use `0.25rem` (4px).
- **Medium Components**: Buttons and cards use `0.5rem` (8px).
- **Large Components**: Large containers or hero sections use `0.75rem` (12px).

## Components

- **Buttons**:
  - *Primary*: Solid Emerald (#10B981) with black text.
  - *Secondary*: Outlined with #2D2D30, white text.
  - *Ghost*: No border, emerald text, subtle #1A1A1C hover background.
- **Inputs**: Dark surfaces (#1A1A1C) with a subtle #2D2D30 border. On focus, the border transitions to Emerald (#10B981). Use DM Mono for placeholder text.
- **Chips/Tags**: Use the Mono font for all tags. Backgrounds should be low-opacity versions of the semantic colors (e.g., 10% Emerald for success tags).
- **Cards**: Background #1A1A1C, 1px border #2D2D30. Padding should be generous (24px or 32px) to maintain the premium feel.
- **Lists**: Separated by thin 1px lines (#2D2D30). Hover states should use a subtle background shift to #242426.
- **Data Tables**: High-density data should use DM Mono for numerical values. Row striping is discouraged; use subtle hover states and clear borders instead.