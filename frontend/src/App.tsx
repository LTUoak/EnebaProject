import { BrowserRouter, Route, Routes } from 'react-router-dom'
import { Layout } from './components/Layout'
import { CartProvider } from './lib/CartProvider'
import { HomePage } from './pages/HomePage'
import { PlaceholderPage } from './pages/PlaceholderPage'
import { ProductPage } from './pages/ProductPage'
import { StorePage } from './pages/StorePage'
import './App.css'

/** Every page address of the site, in one place. All pages share the Layout frame. */
export default function App() {
  return (
    <CartProvider>
      <BrowserRouter>
        <Routes>
          <Route element={<Layout />}>
            <Route path="/" element={<HomePage />} />
            <Route path="/store" element={<StorePage />} />
            <Route path="/product/:slug" element={<ProductPage />} />
            <Route path="/cart" element={<PlaceholderPage title="Cart" />} />
            <Route path="/sign-in" element={<PlaceholderPage title="Sign in" />} />
            <Route path="/sign-up" element={<PlaceholderPage title="Sign up" />} />
            <Route path="/checkout" element={<PlaceholderPage title="Checkout" />} />
            <Route path="/orders" element={<PlaceholderPage title="Order history" />} />
            <Route
              path="*"
              element={<PlaceholderPage title="Page not found" text="There is nothing at this address." />}
            />
          </Route>
        </Routes>
      </BrowserRouter>
    </CartProvider>
  )
}
