import { SITE_NAME } from '../config'
import './Footer.css'

export function Footer() {
  return (
    <footer className="footer">
      <div className="container footer-inner">
        <p>
          <strong>{SITE_NAME}</strong> is a student project. Payments are simulated and all game keys are fake.
        </p>
      </div>
    </footer>
  )
}
