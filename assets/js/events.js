(function () {
  "use strict";

  const mounts = document.querySelectorAll("[data-event-list]");
  if (!mounts.length) return;

  function addText(parent, tagName, className, text) {
    const element = document.createElement(tagName);
    if (className) element.className = className;
    element.textContent = text || "";
    parent.appendChild(element);
    return element;
  }

  function formatEventDate(event) {
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
    return event.programme_year || "";
  }

  function eventImageUrl(path) {
    const baseUrl = path.startsWith("assets/uploads/events/")
      ? window.EVENTS_UPLOADS_BASE_URL
      : window.EVENTS_ASSETS_BASE_URL;
    return new URL(path, baseUrl).href;
  }

  function renderEvent(event) {
    const container = document.createElement("article");
    container.className = "container event-record";
    container.id = event.slug;

    const heading = addText(container, "h2", "h4 text-center pb-3 fw-bold", event.title);
    heading.id = `${event.slug}-title`;

    const row = document.createElement("div");
    row.className = "row gy-4";
    container.appendChild(row);

    const galleryColumn = document.createElement("div");
    galleryColumn.className = "col-lg-6";
    row.appendChild(galleryColumn);

    const imagePaths = [...new Set([event.cover_image, ...(event.gallery || [])].filter(Boolean))];
    if (imagePaths.length) {
      const slider = document.createElement("div");
      slider.className = "portfolio-details-slider swiper js-event-swiper";
      const wrapper = document.createElement("div");
      wrapper.className = "swiper-wrapper align-items-center";
      imagePaths.forEach((path) => {
        const slide = document.createElement("div");
        slide.className = "swiper-slide";
        const image = document.createElement("img");
        image.src = eventImageUrl(path);
        image.alt = event.title;
        image.loading = "lazy";
        slide.appendChild(image);
        wrapper.appendChild(slide);
      });
      slider.appendChild(wrapper);
      const pagination = document.createElement("div");
      pagination.className = "swiper-pagination";
      slider.appendChild(pagination);
      galleryColumn.appendChild(slider);
    } else {
      addText(galleryColumn, "p", "text-muted", "Event images will be added soon.");
    }

    const detailsColumn = document.createElement("div");
    detailsColumn.className = "col-lg-6";
    row.appendChild(detailsColumn);

    const info = document.createElement("div");
    info.className = "portfolio-info";
    detailsColumn.appendChild(info);
    addText(info, "h4", "", "Programme information");
    const list = document.createElement("ul");
    info.appendChild(list);

    const details = [
      ["Category", event.category],
      ["Date", formatEventDate(event)],
      ["Participants", event.participants],
      ["Programme duration", event.duration]
    ];
    details.forEach(([label, value]) => {
      if (!value) return;
      const item = document.createElement("li");
      const strong = document.createElement("strong");
      strong.textContent = `${label}: `;
      item.append(strong, document.createTextNode(value));
      list.appendChild(item);
    });

    const description = addText(detailsColumn, "div", "portfolio-description", "");
    const paragraph = addText(description, "p", "", event.description);
    paragraph.style.whiteSpace = "pre-line";
    return container;
  }

  function showMessage(mount, message, className) {
    mount.replaceChildren();
    addText(mount, "p", className || "text-center", message);
  }

  async function loadEvents(mount) {
    const params = new URLSearchParams();
    const limit = mount.dataset.limit;
    if (limit) params.set("limit", limit);
    if (mount.dataset.featured === "true") params.set("featured", "1");

    try {
      const url = new URL("events.php", window.EVENTS_API_BASE_URL);
      url.search = params.toString();
      const response = await fetch(url, {
        credentials: "omit",
        headers: { Accept: "application/json" }
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.error || "Unable to load events.");

      mount.replaceChildren();
      if (!data.events.length) {
        showMessage(mount, "There are no published events yet.", "text-center text-muted");
        return;
      }

      data.events.forEach((event) => {
        mount.appendChild(renderEvent(event));
        mount.appendChild(document.createElement("br"));
        mount.appendChild(document.createElement("br"));
      });

      mount.querySelectorAll(".js-event-swiper").forEach((element) => {
        if (typeof Swiper === "function") {
          new Swiper(element, {
            loop: element.querySelectorAll(".swiper-slide").length > 1,
            speed: 600,
            autoplay: { delay: 5000 },
            slidesPerView: "auto",
            pagination: {
              el: element.querySelector(".swiper-pagination"),
              type: "bullets",
              clickable: true
            }
          });
        }
      });

      if (typeof AOS !== "undefined") AOS.refresh();
    } catch (error) {
      showMessage(mount, error.message || "Unable to load events. Please try again later.", "text-center text-danger");
    }
  }

  mounts.forEach(loadEvents);
})();
