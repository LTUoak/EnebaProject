import { Outlet } from 'react-router-dom'
import { Footer } from './Footer'
import { Header } from './Header'

/**
 * The frame shared by every page: header on top, footer at the bottom, the page
 * in between. Pages decide their own width, so the homepage banner can span
 * the whole screen while other content stays in the centred column.
 */
export function Layout() {
  return (
    <>
      <Header />
      <main className="page">
        <Outlet />
      </main>
      <Footer />
    </>
  )
}
