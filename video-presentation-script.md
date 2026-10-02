# WJRC Computer Services System — Video Presentation Script (5-7 minutes)

**Format:** [Narration] with (on-screen action) cues. Read at a natural pace — the whole script is timed to land around 6 minutes.

**Updated:** reflects the current system, including the new Parts Out approval workflow, technicians' new read-only Inventory access, and the simplified POS pricing (total = product lines + labor; the earlier VAT/Senior-PWD discount description was removed as it's no longer in the code).

---

## 1. Introduction (≈30 sec)

[Narration]
"Good day! Today we're presenting our capstone system, the **WJRC Computer Services Management System** — a web-based platform built for WJRC Computer Services to manage its day-to-day operations: repair job orders, point-of-sale transactions, inventory, staff accounts, and sales forecasting, all in one place.

The system was built to replace manual, paper-based tracking with a centralized, role-based platform — so the admin, cashier, and technician all work from the same real-time data, with access limited to what each role actually needs."

(on-screen: show the login page with the WJRC logo)

---

## 2. Sign-Up Process (≈45 sec)

[Narration]
"Unlike public consumer apps, WJRC is an internal business system, so there's no open self-registration page — accounts are created and issued by the Admin through the **User Management** module. This keeps control over who gets access to company data.

Let's walk through creating a new staff account."

(on-screen: log in as Admin → go to Users → click to add a new user)

[Narration]
"The admin fills in the staff member's name, email, and optional username and contact number, then assigns a **role** — Admin, Technician, or Cashier — and a **status**, active or inactive.

Notice the required-field validation: name and email are required, and the email and username must be unique across the system — the system will reject a duplicate right away.

For the password, we enforce a strong password policy: at least 11 characters, with an uppercase letter, a lowercase letter, a number, and at least two special characters. This is validated both on submit and shown to the admin as a requirement, so weak passwords are never accepted."

(on-screen: try a weak password to show the validation error, then a valid one)

[Narration]
"Once saved, the account is created with a hashed password and the correct module permissions automatically applied based on its role — and the action is recorded in the Activity Log for accountability."

---

## 3. Login Process (≈45 sec)

[Narration]
"Now let's log in as that new user."

(on-screen: log out, go to /login)

[Narration]
"The login form asks for an email and password. Let's first try an incorrect password."

(on-screen: submit wrong credentials)

[Narration]
"The system responds with a generic error — 'These credentials do not match our records' — without revealing whether the email or the password was wrong. This is a deliberate security choice: it prevents attackers from figuring out which emails are registered in the system.

Now with the correct credentials..."

(on-screen: submit correct credentials)

[Narration]
"...the user is authenticated and redirected straight to the page their role actually uses — Technicians land on Job Orders, Cashiers land on Inventory and Point-of-Sale, and Admins land on the main Dashboard.

Also worth noting: if an account has been deactivated by the admin, login is blocked immediately with a clear message, even if the password is correct."

(optional on-screen: show a deactivated account being denied login)

---

## 4. Security Features (≈75-90 sec)

[Narration]
"Security was a core design consideration, so let's highlight what's under the hood.

**Password protection** — passwords are never stored in plain text. We use Laravel's Bcrypt hashing, and every new or reset password must pass our strong-password rule: minimum 11 characters, mixed case, a number, and two special characters.

**Authentication and session handling** — after a successful login, the session ID is regenerated to prevent session fixation attacks, and on logout, the session is fully invalidated and the CSRF token is regenerated. We also send no-cache headers on protected pages, so a user can't hit the browser's Back button after logging out and see cached, sensitive pages.

**Role-based access control** — every route in the system is protected by role middleware, right down to individual tabs. For example, only Admins can reach User Management, the Dashboard, or Forecasting; only Admins and Technicians can reach Job Orders; Admins and Cashiers get full Inventory tools, while Technicians get a read-only Catalog and Archive view plus the ability to submit Parts Out requests. If a user tries to access a page outside their role, they're redirected to their own home page instead of seeing an error page that leaks the app's structure."

(on-screen: log in as a Technician and try to visit /users or /dashboard directly via the URL, show the redirect)

[Narration]
"**Forgot-password flow** — instead of a plain reset link, we use a One-Time PIN sent by email. The code expires after 2 minutes, allows a maximum of 5 attempts before it's invalidated, and there's a 60-second cooldown before a new code can be requested — protecting against brute-force guessing. The confirmation message is also worded so it never confirms whether an email exists in the system, preventing account enumeration.

**Audit trail** — every sensitive action — account creation, role changes, password resets, deactivations, and now Parts Out requests, approvals, and rejections — is written to an Activity Log with the staff member, timestamp, and IP address, exportable to CSV for record-keeping."

(on-screen: quickly show the Activity Log / Password Reset Log screen)

---

## 5. Main Dashboard / Modules Overview (≈60 sec)

[Narration]
"Once logged in as Admin, we land on the main Dashboard — a real-time snapshot of the business."

(on-screen: Dashboard page)

[Narration]
"At a glance, it shows total revenue, active and pending job orders, total clients, a job-order status breakdown across the full repair pipeline — pending, in progress, for pick-up, completed, cancelled — and a paid-versus-partial payment health bar. There's also a demand and revenue trend chart over the last 6 or 12 months.

From the sidebar, the system is organized into five main modules:

1. **Dashboard** — the business overview we just saw, admin-only.
2. **Product Transaction / Point-of-Sale** — for processing walk-in sales and billing completed job orders.
3. **Job Orders** — the repair-ticket pipeline, from intake to completion.
4. **User Management** — staff accounts, roles, and the activity/password-reset logs.
5. **Sales Forecasting** — data-driven demand and revenue projections.

Inside Inventory there's also a dedicated **Parts Out** tab, which we'll demonstrate shortly. Each staff member only sees the modules and tabs their role grants — technicians, for instance, now see Job Orders plus a read-only Inventory view and Parts Out, keeping their workspace focused without giving them full stock or pricing control."

---

## 6. Key Functionalities and Core Features (≈110-130 sec)

[Narration]
"Let's walk through the core features with real examples.

**Job Orders** — this is the heart of the repair workflow."

(on-screen: Job Orders module)

[Narration]
"An admin creates a job order by entering the customer's details, their device, and the service needed. The system automatically creates or matches the customer record, and assigns a technician. As the technician works on it, they update its progress — pending, in progress, for pick-up, completed — and every status change is logged with a before-and-after record. Once complete, a printable service report can be generated for the customer."

(on-screen: change a job order's status, then open the service report)

[Narration]
"**Point-of-Sale** — this handles two kinds of sales in one till: a straight walk-in product sale, or billing an existing job order's labor cost plus any add-on parts. The total is computed server-side as product lines plus labor, so the amount charged can't be tampered with from the browser."

(on-screen: process a quick sale)

[Narration]
"**Parts Out** — this is a newer addition to the system, and it solves a real gap: tracking parts the moment a technician uses them, not just when the customer eventually pays."

(on-screen: log in as Technician → Inventory → Parts Out tab → submit a request against a job order)

[Narration]
"A technician picks the job order and the parts used, and submits the request. It goes in as **pending approval** — stock isn't touched yet. Switching to the Admin view..."

(on-screen: switch to Admin → Inventory → Parts Out → approve the pending request)

[Narration]
"...the admin can approve or reject it, with a reason required for rejections. On approval, stock is deducted immediately and the part's cost is automatically added to that job order's bill. Once the customer settles at the counter, the admin marks the request as billed. The whole log is filterable and exportable to CSV.

**Inventory Management** — admins and cashiers can add products, adjust stock and pricing, and archive discontinued items rather than permanently deleting them, preserving sales history. Inventory and sales data can also be exported to or imported from CSV for bulk updates and reporting.

**Sales Forecasting** — this is one of our system's standout features. Using historical job-order and sales data, it runs a SARIMA time-series model to forecast demand and revenue up to several months ahead, with a configurable confidence interval. It also surfaces top-performing services and products, day-of-week demand patterns, and holiday impact — giving the owner data to plan staffing and inventory ahead of time, rather than reacting after the fact.

**User Management and Auditing**, which we touched on earlier, rounds out the system — giving the admin full control over staff accounts and a complete, exportable audit trail of everything that happens in the system."

(on-screen: brief look at the Forecasting chart)

---

## 7. Closing (≈15-20 sec)

[Narration]
"To summarize: the WJRC Computer Services Management System brings together secure, role-based account management, a complete repair job-order pipeline with parts tracking, point-of-sale and inventory management, and data-driven sales forecasting — all in a single, auditable platform.

Thank you for watching."

(on-screen: WJRC logo / end card)

---

### Timing guide
| Section | Approx. time |
|---|---|
| Introduction | 0:00 – 0:30 |
| Sign-Up Process | 0:30 – 1:15 |
| Login Process | 1:15 – 2:00 |
| Security Features | 2:00 – 3:30 |
| Dashboard/Modules | 3:30 – 4:30 |
| Key Functionalities | 4:30 – 6:20 |
| Closing | 6:20 – 6:40 |

**Total runtime: ~6.5-7 minutes** — trim the Job Orders or Inventory examples slightly if you need to land closer to 6 minutes; the Parts Out walkthrough is the one section worth keeping full-length since it's the newest feature.
