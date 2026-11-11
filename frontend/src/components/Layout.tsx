import { Outlet } from 'react-router-dom'
import { Footer } from './Footer'
import { Header } from './Header'

/** The frame shared by every page: header on top, footer at the bottom, the page in between. */
export function Layout() {
  return (
    <>
      <Header />
      <main className="container page">
        <Outlet />
      </main>
      <Footer />
    </>
  )
}
