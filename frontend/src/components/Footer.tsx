import { SITE_NAME } from '../config'
import './Footer.css'

export function Footer() {
  return (
    <footer className="footer">
      <div className="container footer-inner">
        <p>
          This is a student project built in the style of {SITE_NAME}. It is not the real {SITE_NAME} store and is
          not affiliated with it. Payments are simulated and all game keys are fake.
        </p>
      </div>
    </footer>
  )
}
