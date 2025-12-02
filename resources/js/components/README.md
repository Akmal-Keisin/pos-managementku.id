# Components Structure

This directory contains all reusable Vue components organized by functionality for better maintainability.

## Directory Structure

```
components/
├── layout/           # Core layout components
│   ├── header/       # Header-related components
│   ├── sidebar/      # Sidebar navigation components
│   └── shell/        # App shell/container components
├── navigation/       # Navigation components (breadcrumbs, etc.)
├── forms/            # Form-specific components
├── patterns/         # Reusable UI patterns
└── ui/               # Base UI components (shadcn-vue + custom)
```

## Component Categories

### Layout (`/layout`)
Core structural components that define the application's layout:
- **AppContent.vue** - Main content container
- **AppLogo.vue** - Application logo component
- **AppLogoIcon.vue** - Logo icon variant
- **header/** - Header components
  - **AppHeader.vue** - Main header with user menu
- **sidebar/** - Sidebar navigation
  - **AppSidebar.vue** - Main sidebar container
  - **AppSidebarHeader.vue** - Sidebar header section
  - **NavMain.vue** - Primary navigation menu
  - **NavFooter.vue** - Sidebar footer
  - **NavUser.vue** - User profile in sidebar
  - **UserInfo.vue** - User information display
  - **UserMenuContent.vue** - User dropdown menu content
- **shell/** - Shell components
  - **AppShell.vue** - Application shell wrapper

### Navigation (`/navigation`)
Navigation-specific components:
- **Breadcrumbs.vue** - Breadcrumb navigation

### Forms (`/forms`)
Form-related components:
- **DeleteUser.vue** - User deletion form

### Patterns (`/patterns`)
Reusable UI patterns:
- **PlaceholderPattern.vue** - Placeholder pattern for empty states

### UI (`/ui`)
Base UI components including shadcn-vue library and custom elements:
- **AppearanceTabs.vue** - Appearance settings tabs
- **Icon.vue** - Icon wrapper component
- **Heading.vue** - Page heading component
- **HeadingSmall.vue** - Small heading variant
- **InputError.vue** - Input error message display
- **TextLink.vue** - Styled link component
- **shadcn-vue components** - Complete UI library (alert-dialog, breadcrumb, collapsible, dialog, input, select, table, etc.)

## Usage

Import components using barrel exports:

```typescript
// Import from category index
import { AppSidebar, NavMain } from '@/components/layout'
import { Breadcrumbs } from '@/components/navigation'
import { DeleteUser } from '@/components/forms'
import { PlaceholderPattern } from '@/components/patterns'
import { Heading, InputError } from '@/components/ui'

// Or import directly
import AppSidebar from '@/components/layout/sidebar/AppSidebar.vue'
```

## Component Organization Guidelines

When adding new components:

1. **Layout** - Structural components that define app layout (headers, sidebars, shells)
2. **Navigation** - Components specifically for navigation (menus, breadcrumbs, tabs)
3. **Forms** - Form-specific components (inputs, validation, form sections)
4. **Patterns** - Reusable UI patterns (cards, lists, empty states)
5. **UI** - Base atomic components and shadcn-vue library

Keep components focused and single-purpose. Create subdirectories within categories when needed (e.g., `layout/header/`, `layout/sidebar/`).

## Index Exports

Each category has an `index.ts` file that exports all components from that category. This enables clean imports and better tree-shaking.

Update the category's `index.ts` when adding new components to ensure they're properly exported.
