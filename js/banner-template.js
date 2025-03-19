// Check if we have a server-generated template from PHP
if (typeof window.bannerTemplate === "undefined") {
  // Create default banner template with optimized HTML structure if no server template exists
  let bannerTemplate = `
  <div class="cookie-consent-banner" role="dialog" aria-modal="true" aria-labelledby="cookie-consent-title" aria-describedby="cookie-consent-description" data-position="bottom">
      <button class="cookie-consent-close" aria-label="Lukk cookie-banner">Lukk <span class="close-x" aria-hidden="true">×</span></button>
      <div class="cookie-consent-content">
          <header>
              <h3 id="cookie-consent-title">Vi bruker informasjonskapsler (cookies)</h3>
              <p id="cookie-consent-description">Vi bruker informasjonskapsler for å forbedre brukeropplevelsen, tilby personlig tilpasset innhold og analysere trafikken vår.</p>
          </header>
          
          <section class="cookie-consent-options" aria-labelledby="cookie-consent-title">
              <div class="cookie-category">
                  <label class="toggle-switch" for="necessary-cookie-toggle">
                      <input type="checkbox" id="necessary-cookie-toggle" checked disabled data-category="necessary">
                      <span class="slider" aria-hidden="true"></span>
                  </label>
                  <div class="category-info">
                      <h4 id="necessary-category-heading">Nødvendige</h4>
                      <p id="necessary-category-description">Disse informasjonskapslene er nødvendige for at nettstedet skal fungere.</p>
                  </div>
              </div>

              <div class="cookie-category">
                  <label class="toggle-switch" for="analytics-cookie-toggle">
                      <input type="checkbox" id="analytics-cookie-toggle" data-category="analytics">
                      <span class="slider" aria-hidden="true"></span>
                  </label>
                  <div class="category-info">
                      <h4 id="analytics-category-heading">Analyse</h4>
                      <p id="analytics-category-description">Disse informasjonskapslene hjelper oss å forstå hvordan besøkende bruker nettstedet.</p>
                  </div>
              </div>

              <div class="cookie-category">
                  <label class="toggle-switch" for="functional-cookie-toggle">
                      <input type="checkbox" id="functional-cookie-toggle" data-category="functional">
                      <span class="slider" aria-hidden="true"></span>
                  </label>
                  <div class="category-info">
                      <h4 id="functional-category-heading">Funksjonell</h4>
                      <p id="functional-category-description">Disse informasjonskapslene gjør at nettstedet kan gi forbedret funksjonalitet.</p>
                  </div>
              </div>
              
              <div class="cookie-category">
                  <label class="toggle-switch" for="marketing-cookie-toggle">
                      <input type="checkbox" id="marketing-cookie-toggle" data-category="marketing">
                      <span class="slider" aria-hidden="true"></span>
                  </label>
                  <div class="category-info">
                      <h4 id="marketing-category-heading">Markedsføring</h4>
                      <p id="marketing-category-description">Disse informasjonskapslene brukes til å vise målrettet markedsføring.</p>
                  </div>
              </div>
          </section>

          <div class="cookie-consent-buttons" role="group" aria-label="Samtykkevalg for informasjonskapsler">
              <button type="button" class="cookie-consent-decline">Avslå alle</button>
              <button type="button" class="cookie-consent-save-custom">Lagre preferanser</button>
              <button type="button" class="cookie-consent-accept">Godta alle</button>
          </div>

          <footer class="cookie-consent-footer">
              <p class="cookie-consent-links">
                  <a href="https://devora.no/informasjonskapsler/" target="_blank" rel="noopener noreferrer">Les mer om informasjonskapsler</a>
              </p>
              <div class="cookie-consent-branding">
                  <a href="https://devora.no" target="_blank" rel="noopener noreferrer" class="devora-branding">
                      Powered by <span class="devora-name">Devora</span>
                  </a>
              </div>
          </footer>
      </div>
  </div>`;

  // Make template available globally
  window.bannerTemplate = bannerTemplate;

  // Only log in debug mode
  if (window.cookieSettings?.debugMode) {
    console.log("Using default template - no server-generated template found");
  }
} else {
  // Only log in debug mode
  if (window.cookieSettings?.debugMode) {
    console.log(
      "Using server-generated template with timestamp: " +
        (window.templateTimestamp || "unknown")
    );
  }
}
