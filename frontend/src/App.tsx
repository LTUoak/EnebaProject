import { BrowserRouter, Route, Routes } from 'react-router-dom'
import { Layout } from './components/Layout'
import { HomePage } from './pages/HomePage'
import { PlaceholderPage } from './pages/PlaceholderPage'
import './App.css'

/** Every page address of the site, in one place. All pages share the Layout frame. */
export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route element={<Layout />}>
          <Route path="/" element={<HomePage />} />
          <Route path="/store" element={<PlaceholderPage title="Store" />} />
          <Route path="/product/:slug" element={<PlaceholderPage title="Product" />} />
          <Route path="/cart" element={<PlaceholderPage title="Cart" />} />
          <Route path="/sign-in" element={<PlaceholderPage title="Sign in" />} />
          <Route path="/sign-up" element={<PlaceholderPage title="Sign up" />} />
          <Route path="/checkout" element={<PlaceholderPage title="Checkout" />} />
          <Route path="/orders" element={<PlaceholderPage title="Order history" />} />
          <Route path="*" element={<PlaceholderPage title="Page not found" />} />
        </Route>
      </Routes>
    </BrowserRouter>
  )
}
