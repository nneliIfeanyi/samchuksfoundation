(function () {
  "use strict";

  const apiUrl = (name) => new URL(name, window.EVENTS_API_BASE_URL);
  const loginScreen = document.querySelector("#login-screen");
  const adminApp = document.querySelector("#admin-app");
  const loginForm = document.querySelector("#login-form");
  const eventForm = document.querySelector("#event-form");
  const eventDialog = document.querySelector("#event-dialog");
  const tableBody = document.querySelector("#events-table-body");
  const toastRegion = document.querySelector("#toast-region");
  let csrfToken = "";
  let events = [];

  function showToast(text, type = "success") {
    const title = type === "error" ? "Something went wrong" : type === "info" ? "Notice" : "Done";
    const iconName = type === "error" ? "bi-exclamation-circle" : type === "info" ? "bi-info-circle" : "bi-check-circle";
    const toast = document.createElement("div");
    toast.className = `admin-toast ${type}`;

    const icon = document.createElement("span");
    icon.className = "toast-icon";
    const iconElement = document.createElement("i");
    iconElement.className = `bi ${iconName}`;
    icon.appendChild(iconElement);

    const copy = document.createElement("span");
    copy.className = "toast-copy";
    const heading = document.createElement("strong");
    heading.textContent = title;
    const detail = document.createElement("span");
    detail.textContent = text;
    copy.append(heading, detail);

    const dismiss = document.createElement("button");
    dismiss.className = "toast-dismiss";
    dismiss.type = "button";
    dismiss.setAttribute("aria-label", "Dismiss notification");
    const dismissIcon = document.createElement("i");
    dismissIcon.className = "bi bi-x";
    dismiss.appendChild(dismissIcon);
    dismiss.addEventListener("click", () => removeToast(toast));

    toast.append(icon, copy, dismiss);
    toastRegion.appendChild(toast);
    window.requestAnimationFrame(() => toast.classList.add("is-visible"));

    const timeout = window.setTimeout(() => removeToast(toast), 5000);
    toast.addEventListener("mouseenter", () => window.clearTimeout(timeout), { once: true });
  }

  function removeToast(toast) {
    toast.classList.remove("is-visible");
    window.setTimeout(() => toast.remove(), 220);
  }

  async function apiRequest(name, options = {}) {
    const headers = new Headers(options.headers || {});
    headers.set("Accept", "application/json");
    if (options.body && !(options.body instanceof FormData)) {
      headers.set("Content-Type", "application/json");
    }
    if (csrfToken && options.method && options.method !== "GET") {
      headers.set("X-CSRF-Token", csrfToken);
    }

    const response = await fetch(apiUrl(name), { ...options, credentials: "include", headers });
    let data;
    try {
      data = await response.json();
    } catch {
      throw new Error("The server returned an unreadable response. Please try again.");
    }
    if (!response.ok) throw new Error(data.error || "The request could not be completed.");
    return data;
  }

  function displayLogin() {
    loginScreen.classList.remove("d-none");
    adminApp.classList.add("d-none");
    document.querySelector("#login-email").focus();
  }

  function displayDashboard(email) {
    loginScreen.classList.add("d-none");
    adminApp.classList.remove("d-none");
    document.querySelector("#admin-email").textContent = email || "Administrator";
  }

  function createCell(className) {
    const cell = document.createElement("td");
    if (className) cell.className = className;
    return cell;
  }

  function createActionButton(iconName, label, className, onClick) {
    const button = document.createElement("button");
    button.type = "button";
    button.className = `row-action${className ? ` ${className}` : ""}`;
    button.setAttribute("aria-label", label);
    button.title = label;
    const icon = document.createElement("i");
    icon.className = `bi ${iconName}`;
    button.appendChild(icon);
    button.addEventListener("click", onClick);
    return button;
  }

  function formatSchedule(event) {
    if (event.event_date) {
      const date = new Date(`${event.event_date}T00:00:00`);
      if (!Number.isNaN(date.getTime())) {
        return new Intl.DateTimeFormat(undefined, {
          year: "numeric",
          month: "short",
          day: "numeric"
        }).format(date);
      }
    }
    return event.programme_year || "Not specified";
  }

  function renderEventRow(event) {
    const row = document.createElement("tr");
    const nameCell = createCell();
    const nameWrap = document.createElement("div");
    nameWrap.className = "event-name-cell";

    if (event.cover_image) {
      const image = document.createElement("img");
      image.className = "event-thumb";
      const imageBaseUrl = event.cover_image.startsWith("assets/uploads/events/")
        ? window.EVENTS_UPLOADS_BASE_URL
        : window.EVENTS_ASSETS_BASE_URL;
      image.src = new URL(event.cover_image, imageBaseUrl).href;
      image.alt = "";
      image.loading = "lazy";
      image.addEventListener("error", () => {
        const placeholder = document.createElement("span");
        placeholder.className = "event-thumb-placeholder";
        const placeholderIcon = document.createElement("i");
        placeholderIcon.className = "bi bi-image";
        placeholder.appendChild(placeholderIcon);
        image.replaceWith(placeholder);
      }, { once: true });
      nameWrap.appendChild(image);
    } else {
      const placeholder = document.createElement("span");
      placeholder.className = "event-thumb-placeholder";
      const placeholderIcon = document.createElement("i");
      placeholderIcon.className = "bi bi-image";
      placeholder.appendChild(placeholderIcon);
      nameWrap.appendChild(placeholder);
    }

    const nameCopy = document.createElement("div");
    const name = document.createElement("strong");
    name.className = "event-name";
    name.textContent = event.title;
    const meta = document.createElement("div");
    meta.className = "event-meta";
    const slug = document.createElement("span");
    slug.textContent = `/${event.slug}`;
    meta.appendChild(slug);
    if (event.featured) {
      const featured = document.createElement("span");
      featured.className = "featured-pill";
      const star = document.createElement("i");
      star.className = "bi bi-star-fill";
      featured.append(star, document.createTextNode("Featured"));
      meta.appendChild(featured);
    }
    nameCopy.append(name, meta);
    nameWrap.appendChild(nameCopy);
    nameCell.appendChild(nameWrap);
    row.appendChild(nameCell);

    const categoryCell = createCell();
    const category = document.createElement("span");
    category.className = "category-pill";
    category.textContent = event.category;
    categoryCell.appendChild(category);
    row.appendChild(categoryCell);

    const scheduleCell = createCell();
    scheduleCell.textContent = formatSchedule(event);
    row.appendChild(scheduleCell);

    const statusCell = createCell();
    const status = document.createElement("span");
    status.className = `status-pill status-${event.status}`;
    status.textContent = event.status;
    statusCell.appendChild(status);
    row.appendChild(statusCell);

    const actionsCell = createCell("text-end");
    const actions = document.createElement("div");
    actions.className = "row-actions";
    actions.append(
      createActionButton("bi-pencil", "Edit event", "", () => openEditor(event)),
      createActionButton("bi-trash3", "Delete event", "delete", () => deleteEvent(event))
    );
    actionsCell.appendChild(actions);
    row.appendChild(actionsCell);
    return row;
  }

  function renderEvents() {
    const query = document.querySelector("#event-search").value.trim().toLowerCase();
    const statusFilter = document.querySelector("#status-filter").value;
    const visibleEvents = events.filter((event) => {
      const matchesStatus = statusFilter === "all" || event.status === statusFilter;
      const searchText = `${event.title} ${event.slug} ${event.category}`.toLowerCase();
      return matchesStatus && (!query || searchText.includes(query));
    });

    document.querySelector("#stat-total").textContent = events.length;
    document.querySelector("#stat-published").textContent = events.filter((event) => event.status === "published").length;
    document.querySelector("#stat-drafts").textContent = events.filter((event) => event.status === "draft").length;
    document.querySelector("#nav-event-count").textContent = events.length;
    document.querySelector("#results-count").textContent = `${visibleEvents.length} ${visibleEvents.length === 1 ? "event" : "events"}`;
    document.querySelector("#table-summary").textContent = query || statusFilter !== "all"
      ? `Showing ${visibleEvents.length} of ${events.length} events`
      : `${events.length} event records`;

    tableBody.replaceChildren();
    if (!visibleEvents.length) {
      const row = document.createElement("tr");
      const cell = document.createElement("td");
      cell.colSpan = 5;
      const empty = document.createElement("div");
      empty.className = "table-empty";
      const icon = document.createElement("i");
      icon.className = `bi ${events.length ? "bi-search" : "bi-calendar2-plus"}`;
      const heading = document.createElement("strong");
      heading.textContent = events.length ? "No matching events" : "No events yet";
      const detail = document.createElement("span");
      detail.textContent = events.length
        ? "Try a different search or status filter."
        : "Add your first event to start building the events page.";
      empty.append(icon, heading, detail);
      if (!events.length) {
        const addButton = document.createElement("button");
        addButton.className = "btn btn-primary btn-sm mt-2";
        addButton.type = "button";
        addButton.textContent = "Add your first event";
        addButton.addEventListener("click", () => openEditor());
        empty.appendChild(addButton);
      }
      cell.appendChild(empty);
      row.appendChild(cell);
      tableBody.appendChild(row);
      return;
    }

    const fragment = document.createDocumentFragment();
    visibleEvents.forEach((event) => fragment.appendChild(renderEventRow(event)));
    tableBody.appendChild(fragment);
  }

  async function loadEvents() {
    const result = await apiRequest("admin-events.php");
    events = result.events;
    renderEvents();
  }

  function openEditor(event) {
    if (event) {
      document.querySelector("#form-heading").textContent = "Edit event";
      document.querySelector("#save-event-button span").textContent = "Save changes";
      document.querySelector("#event-id").value = event.id;
      document.querySelector("#title").value = event.title;
      document.querySelector("#slug").value = event.slug;
      document.querySelector("#slug").dataset.edited = "true";
      document.querySelector("#category").value = event.category;
      document.querySelector("#event-date").value = event.event_date || "";
      document.querySelector("#programme-year").value = event.programme_year || "";
      document.querySelector("#participants").value = event.participants || "";
      document.querySelector("#duration").value = event.duration || "";
      document.querySelector("#description").value = event.description;
      document.querySelector("#cover-image").value = event.cover_image || "";
      document.querySelector("#gallery").value = (event.gallery || []).join("\n");
      document.querySelector("#status").value = event.status;
      document.querySelector("#featured").checked = event.featured;
    } else {
      clearForm();
    }
    if (!eventDialog.open) eventDialog.showModal();
  }

  function clearForm() {
    eventForm.reset();
    document.querySelector("#event-id").value = "";
    document.querySelector("#slug").removeAttribute("data-edited");
    document.querySelector("#form-heading").textContent = "Create an event";
    document.querySelector("#save-event-button span").textContent = "Save event";
  }

  async function deleteEvent(event) {
    if (!window.confirm(`Delete "${event.title}"? This cannot be undone.`)) return;
    try {
      await apiRequest(`admin-events.php?id=${encodeURIComponent(event.id)}`, { method: "DELETE" });
      if (document.querySelector("#event-id").value === String(event.id)) {
        eventDialog.close();
        clearForm();
      }
      await loadEvents();
      showToast("The event was deleted.");
    } catch (error) {
      showToast(error.message, "error");
    }
  }

  loginForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    const submitButton = loginForm.querySelector("button[type='submit']");
    submitButton.disabled = true;
    try {
      const result = await apiRequest("auth.php", {
        method: "POST",
        body: JSON.stringify({
          email: document.querySelector("#login-email").value,
          password: document.querySelector("#login-password").value
        })
      });
      csrfToken = result.csrfToken;
      displayDashboard(result.email);
      showToast("You're signed in to the events workspace.");
      try {
        await loadEvents();
      } catch (error) {
        showToast(error.message, "error");
      }
    } catch (error) {
      showToast(error.message, "error");
    } finally {
      submitButton.disabled = false;
    }
  });

  document.querySelector("#logout-button").addEventListener("click", async () => {
    try {
      await apiRequest("auth.php", { method: "DELETE" });
      csrfToken = "";
      loginForm.reset();
      closeSidebar();
      displayLogin();
      showToast("You have signed out.");
    } catch (error) {
      showToast(error.message, "error");
    }
  });

  document.querySelector("#new-event-button").addEventListener("click", () => openEditor());
  document.querySelector("#close-dialog-button").addEventListener("click", () => eventDialog.close());
  document.querySelector("#cancel-event-button").addEventListener("click", () => eventDialog.close());
  eventDialog.addEventListener("click", (event) => {
    if (event.target === eventDialog) eventDialog.close();
  });
  eventDialog.addEventListener("close", clearForm);

  document.querySelector("#title").addEventListener("input", (event) => {
    const slugInput = document.querySelector("#slug");
    if (!slugInput.dataset.edited) {
      slugInput.value = event.target.value.toLowerCase().trim()
        .replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
    }
  });
  document.querySelector("#slug").addEventListener("input", (event) => {
    event.target.dataset.edited = "true";
  });

  eventForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    const id = document.querySelector("#event-id").value;
    const saveButton = document.querySelector("#save-event-button");
    const payload = {
      title: document.querySelector("#title").value,
      slug: document.querySelector("#slug").value,
      category: document.querySelector("#category").value,
      event_date: document.querySelector("#event-date").value,
      programme_year: document.querySelector("#programme-year").value,
      participants: document.querySelector("#participants").value,
      duration: document.querySelector("#duration").value,
      description: document.querySelector("#description").value,
      cover_image: document.querySelector("#cover-image").value,
      gallery: document.querySelector("#gallery").value.split(/\r?\n/).map((path) => path.trim()).filter(Boolean),
      status: document.querySelector("#status").value,
      featured: document.querySelector("#featured").checked
    };

    saveButton.disabled = true;
    try {
      await apiRequest(id ? `admin-events.php?id=${encodeURIComponent(id)}` : "admin-events.php", {
        method: id ? "PUT" : "POST",
        body: JSON.stringify(payload)
      });
      await loadEvents();
      eventDialog.close();
      showToast(id ? "Your event changes have been saved." : "Your event has been created.");
    } catch (error) {
      showToast(error.message, "error");
    } finally {
      saveButton.disabled = false;
    }
  });

  document.querySelector("#upload-button").addEventListener("click", async (event) => {
    const button = event.currentTarget;
    const files = document.querySelector("#image-upload").files;
    if (!files.length) {
      showToast("Choose one or more images to upload.", "info");
      return;
    }
    const formData = new FormData();
    Array.from(files).forEach((file) => formData.append("images[]", file));
    button.disabled = true;
    try {
      const result = await apiRequest("upload.php", { method: "POST", body: formData });
      const gallery = document.querySelector("#gallery");
      gallery.value = [gallery.value.trim(), ...result.images].filter(Boolean).join("\n");
      if (!document.querySelector("#cover-image").value) {
        document.querySelector("#cover-image").value = result.images[0];
      }
      document.querySelector("#image-upload").value = "";
      showToast("Images uploaded. Save the event to apply them.");
    } catch (error) {
      showToast(error.message, "error");
    } finally {
      button.disabled = false;
    }
  });

  document.querySelector("#event-search").addEventListener("input", renderEvents);
  document.querySelector("#status-filter").addEventListener("change", renderEvents);
  document.addEventListener("keydown", (event) => {
    if (event.key === "/" && !event.ctrlKey && !event.metaKey &&
        !["INPUT", "TEXTAREA", "SELECT"].includes(document.activeElement.tagName) &&
        !eventDialog.open && !adminApp.classList.contains("d-none")) {
      event.preventDefault();
      document.querySelector("#event-search").focus();
    }
  });

  const sidebarToggle = document.querySelector("#sidebar-toggle");
  const sidebarOverlay = document.querySelector("#sidebar-overlay");

  function setSidebarOpen(isOpen) {
    adminApp.classList.toggle("sidebar-open", isOpen);
    sidebarToggle.setAttribute("aria-expanded", String(isOpen));
    sidebarToggle.setAttribute("aria-label", isOpen ? "Close navigation" : "Open navigation");
  }

  function closeSidebar() {
    setSidebarOpen(false);
  }

  sidebarToggle.addEventListener("click", () => {
    setSidebarOpen(!adminApp.classList.contains("sidebar-open"));
  });
  sidebarOverlay.addEventListener("click", closeSidebar);
  document.querySelectorAll(".sidebar-link").forEach((link) => {
    link.addEventListener("click", closeSidebar);
  });
  document.querySelector("#current-year").textContent = new Date().getFullYear();

  (async function initialize() {
    try {
      const session = await apiRequest("auth.php");
      if (!session.authenticated) {
        displayLogin();
        return;
      }
      csrfToken = session.csrfToken;
      displayDashboard(session.email);
      try {
        await loadEvents();
      } catch (error) {
        showToast(error.message, "error");
      }
    } catch (error) {
      displayLogin();
      showToast(error.message, "error");
    }
  })();
})();
