import { BrowserRouter, Route, Routes } from 'react-router-dom'
import { Layout } from './components/Layout'
import { RequireAuth } from './components/RequireAuth'
import { AuthProvider } from './lib/AuthProvider'
import { CartProvider } from './lib/CartProvider'
import { AuthPage } from './pages/AuthPage'
import { CartPage } from './pages/CartPage'
import { CheckoutPage } from './pages/CheckoutPage'
import { HomePage } from './pages/HomePage'
import { OrderPage, OrdersPage } from './pages/OrdersPage'
import { PlaceholderPage } from './pages/PlaceholderPage'
import { ProductPage } from './pages/ProductPage'
import { StorePage } from './pages/StorePage'
import './App.css'

/** Every page address of the site, in one place. All pages share the Layout frame. */
export default function App() {
  return (
    <AuthProvider>
    <CartProvider>
      <BrowserRouter>
        <Routes>
          <Route element={<Layout />}>
            <Route path="/" element={<HomePage />} />
            <Route path="/store" element={<StorePage />} />
            <Route path="/product/:slug" element={<ProductPage />} />
            <Route path="/cart" element={<CartPage />} />
            <Route path="/sign-in" element={<AuthPage key="sign-in" mode="sign-in" />} />
            <Route path="/sign-up" element={<AuthPage key="sign-up" mode="sign-up" />} />
            {/* Protected pages: RequireAuth sends signed-out visitors to the sign-in page first. */}
            <Route path="/checkout" element={<RequireAuth><CheckoutPage /></RequireAuth>} />
            <Route path="/orders" element={<RequireAuth><OrdersPage /></RequireAuth>} />
            <Route path="/orders/:id" element={<RequireAuth><OrderPage /></RequireAuth>} />
            <Route
              path="*"
              element={<PlaceholderPage title="Page not found" text="There is nothing at this address." />}
            />
          </Route>
        </Routes>
      </BrowserRouter>
    </CartProvider>
    </AuthProvider>
  )
}
