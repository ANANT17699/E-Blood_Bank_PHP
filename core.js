"use strict";

const ThemeManager = {
  STORAGE_KEY: "ebb-theme",

  init() {
    const saved = localStorage.getItem(this.STORAGE_KEY);
    const prefersDark = window.matchMedia(
      "(prefers-color-scheme: dark)",
    ).matches;
    const theme = saved || (prefersDark ? "dark" : "light");
    this.apply(theme);

    window
      .matchMedia("(prefers-color-scheme: dark)")
      .addEventListener("change", (e) => {
        if (!localStorage.getItem(this.STORAGE_KEY)) {
          this.apply(e.matches ? "dark" : "light");
        }
      });
  },

  apply(theme) {
    document.documentElement.setAttribute("data-theme", theme);
    this.updateToggles(theme);
  },

  toggle() {
    const current =
      document.documentElement.getAttribute("data-theme") || "light";
    const next = current === "dark" ? "light" : "dark";
    this.apply(next);
    localStorage.setItem(this.STORAGE_KEY, next);
    showToast(
      `${next === "dark" ? "🌙 Dark" : "☀️ Light"} mode enabled`,
      "info",
    );
  },

  updateToggles(theme) {
    document.querySelectorAll(".theme-toggle").forEach((btn) => {
      btn.innerHTML = theme === "dark" ? "☀️" : "🌙";
      btn.setAttribute(
        "aria-label",
        `Switch to ${theme === "dark" ? "light" : "dark"} mode`,
      );
      btn.setAttribute(
        "title",
        `Switch to ${theme === "dark" ? "light" : "dark"} mode`,
      );
    });
  },
};

const Navbar = {
  init() {
    this.navbar = document.querySelector(".navbar");
    this.hamburger = document.querySelector(".hamburger");
    this.mobileNav = document.querySelector(".mobile-nav");
    this._isOpen = false;

    if (!this.navbar) return;

    window.addEventListener(
      "scroll",
      () => {
        this.navbar.classList.toggle("scrolled", window.scrollY > 10);
      },
      { passive: true },
    );

    if (this.hamburger) {
      this.hamburger.addEventListener("click", () => this.toggleMobile());
    }

    document.addEventListener("click", (e) => {
      if (
        this._isOpen &&
        !this.mobileNav?.contains(e.target) &&
        !this.hamburger?.contains(e.target)
      ) {
        this.closeMobile();
      }
    });

    this.setActiveLink();
  },

  toggleMobile() {
    this._isOpen ? this.closeMobile() : this.openMobile();
  },

  openMobile() {
    this._isOpen = true;
    this.mobileNav?.classList.add("open");
    this.hamburger?.classList.add("open");
    document.body.style.overflow = "hidden";
  },

  closeMobile() {
    this._isOpen = false;
    this.mobileNav?.classList.remove("open");
    this.hamburger?.classList.remove("open");
    document.body.style.overflow = "";
  },

  setActiveLink() {
    const current = window.location.pathname.split("/").pop() || "index.html";
    document.querySelectorAll(".nav-link").forEach((link) => {
      const href = link.getAttribute("href")?.split("/").pop();
      if (href === current) link.classList.add("active");
    });
  },
};

const Sidebar = {
  STORAGE_KEY: "ebb-sidebar-open",

  init() {
    this.sidebar = document.querySelector(".sidebar");
    this.overlay = document.querySelector(".sidebar-overlay");
    this.toggleBtn = document.querySelector(".sidebar-toggle-btn");
    this.closeBtn = document.querySelector(".sidebar-close");
    this.mainContent = document.querySelector(".main-content");

    if (!this.sidebar) return;

    const isMobile = window.innerWidth < 768;
    const saved = localStorage.getItem(this.STORAGE_KEY);

    if (isMobile) {
      this.close();
    } else {
      const shouldOpen = saved === null ? true : saved === "true";
      shouldOpen ? this.open() : this.close();
    }

    this.toggleBtn?.addEventListener("click", () => this.toggle());
    this.closeBtn?.addEventListener("click", () => this.close());
    this.overlay?.addEventListener("click", () => this.close());
    this.setActiveLink();

    window.addEventListener("resize", () => {
      if (window.innerWidth < 768) this.close();
    });
  },

  open() {
    this.sidebar?.classList.remove("collapsed");
    this.mainContent?.classList.remove("expanded");
    if (window.innerWidth < 768) {
      this.overlay?.classList.add("show");
      document.body.style.overflow = "hidden";
    }
  },

  close() {
    this.sidebar?.classList.add("collapsed");
    this.mainContent?.classList.add("expanded");
    this.overlay?.classList.remove("show");
    document.body.style.overflow = "";
  },

  toggle() {
    const isClosed = this.sidebar?.classList.contains("collapsed");
    isClosed ? this.open() : this.close();
    if (window.innerWidth >= 768) {
      localStorage.setItem(this.STORAGE_KEY, String(isClosed));
    }
  },

  setActiveLink() {
    const current = window.location.pathname.split("/").pop() || "index.html";
    document.querySelectorAll(".sidebar-link").forEach((link) => {
      const href = link.getAttribute("href")?.split("/").pop();
      if (href === current) link.classList.add("active");
    });
  },
};

function showToast(message, type = "info", duration = 3500) {
  let container = document.querySelector(".toast-container");
  if (!container) {
    container = document.createElement("div");
    container.className = "toast-container";
    document.body.appendChild(container);
  }

  const icons = { success: "✅", error: "❌", warning: "⚠️", info: "ℹ️" };
  const toast = document.createElement("div");
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `
    <span class="toast-icon">${icons[type] || icons.info}</span>
    <span>${message}</span>
  `;

  container.appendChild(toast);

  setTimeout(() => {
    toast.classList.add("removing");
    toast.addEventListener("animationend", () => toast.remove());
  }, duration);
}

window.ThemeManager = ThemeManager;
window.showToast = showToast;
