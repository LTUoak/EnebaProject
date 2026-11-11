// Small line icons drawn as inline SVG, so no icon library or image files are needed.
// They take their colour from the surrounding text (currentColor).

import type { ReactNode } from 'react'

function Icon({ children, size = 26 }: { children: ReactNode; size?: number }) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.7"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
    >
      {children}
    </svg>
  )
}

export const SearchIcon = () => (
  <Icon>
    <circle cx="11" cy="11" r="7" />
    <path d="m20 20-3.8-3.8" />
  </Icon>
)

export const CartIcon = () => (
  <Icon>
    <path d="M3 4h2.5l2.2 10.5h10.6L20.5 7H6.4" />
    <circle cx="9" cy="19" r="1.4" />
    <circle cx="17" cy="19" r="1.4" />
  </Icon>
)

export const UserIcon = () => (
  <Icon>
    <circle cx="12" cy="8" r="4" />
    <path d="M4.5 20.5c1.2-3.6 4-5.5 7.5-5.5s6.300 1.900 7.500 5.500" />
  </Icon>
)

export const CloseIcon = () => (
  <Icon>
    <path d="M6 6l12 12M18 6 6 18" />
  </Icon>
)

export const GamepadIcon = ({ size = 34 }: { size?: number }) => (
  <Icon size={size}>
    <path d="M7 8h10a4 4 0 0 1 4 4l-.6 4.200a2.300 2.300 0 0 1-4.100 1L15 15.500H9l-1.300 1.700a2.300 2.300 0 0 1-4.100-1L3 12a4 4 0 0 1 4-4Z" />
    <path d="M8 11v3M6.500 12.500h3" />
    <circle cx="15.500" cy="11.500" r=".6" fill="currentColor" />
    <circle cx="17.500" cy="13.500" r=".6" fill="currentColor" />
  </Icon>
)

export const TagIcon = ({ size = 34 }: { size?: number }) => (
  <Icon size={size}>
    <path d="M3.500 12.500V4.500h8l9 9-8 8-9-9Z" />
    <circle cx="8" cy="9" r="1.400" />
  </Icon>
)

/** A rounded badge with one or two letters, used for platforms without drawing their logos. */
export const MonogramIcon = ({ letters, size = 34 }: { letters: string; size?: number }) => (
  <svg width={size} height={size} viewBox="0 0 24 24" aria-hidden="true">
    <rect x="2.500" y="2.500" width="19" height="19" rx="6" fill="none" stroke="currentColor" strokeWidth="1.700" />
    <text
      x="12"
      y="12.500"
      textAnchor="middle"
      dominantBaseline="middle"
      fontSize={letters.length > 1 ? 8.500 : 11}
      fontWeight="800"
      fill="currentColor"
    >
      {letters}
    </text>
  </svg>
)
