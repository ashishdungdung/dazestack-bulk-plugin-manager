import fs from "node:fs";
import path from "node:path";
import { execSync } from "node:child_process";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const LANG_DIR = path.resolve(__dirname, "../languages");

const translations = {
  es_ES: {
    "DazeStack Bulk Plugin Manager": "Gestor Masivo de Plugins DazeStack",
    "High-efficiency bulk provisioning for WordPress plugin stacks.": "Aprovisionamiento masivo de alta eficiencia para pilas de plugins de WordPress.",
    "Dashboard": "Panel de Control",
    "Bulk Plugin Manager": "Gestor Masivo de Plugins",
    "About & Legal": "Acerca de y Legal",
    "⭐ Review & Rate Us": "⭐ Revisar y Calificarnos",
    "Review & Rate Us": "Revisar y Calificarnos",
    "Recipe Library & Playbooks": "Biblioteca de Recetas y Guías",
    "Save Recipe": "Guardar Receta",
    "Export WP-CLI": "Exportar WP-CLI",
    "Copy to Clipboard": "Copiar al Portapapeles",
    "Queue Completed": "Cola Completada",
    "Close": "Cerrar",
    "Done": "Listo",
    "Cancel": "Cancelar",
    "Installed": "Instalado",
    "Live": "En Vivo",
    "Delete": "Eliminar",
    "All clear.": "Todo despejado.",
    "Insufficient permissions.": "Permisos insuficientes.",
    "Security alert: critical vulnerability detected.": "Alerta de seguridad: vulnerabilidad crítica detectada.",
    "Filesystem Credentials Required": "Credenciales del Sistema de Archivos Requeridas"
  },
  de_DE: {
    "DazeStack Bulk Plugin Manager": "DazeStack Massen-Plugin-Manager",
    "High-efficiency bulk provisioning for WordPress plugin stacks.": "Hocheffiziente Massenbereitstellung für WordPress-Plugin-Stacks.",
    "Dashboard": "Übersicht",
    "Bulk Plugin Manager": "Massen-Plugin-Manager",
    "About & Legal": "Über uns & Rechtliches",
    "⭐ Review & Rate Us": "⭐ Bewerten Sie uns",
    "Review & Rate Us": "Bewerten Sie uns",
    "Recipe Library & Playbooks": "Rezeptbibliothek & Vorlagen",
    "Save Recipe": "Rezept speichern",
    "Export WP-CLI": "WP-CLI exportieren",
    "Copy to Clipboard": "In Zwischenablage kopieren",
    "Queue Completed": "Warteschlange abgeschlossen",
    "Close": "Schließen",
    "Done": "Fertig",
    "Cancel": "Abbrechen",
    "Installed": "Installiert",
    "Live": "Aktiv",
    "Delete": "Löschen",
    "All clear.": "Alles in Ordnung.",
    "Insufficient permissions.": "Unzureichende Berechtigungen.",
    "Security alert: critical vulnerability detected.": "Sicherheitswarnung: Kritische Schwachstelle erkannt.",
    "Filesystem Credentials Required": "Dateisystem-Anmeldeinformationen erforderlich"
  },
  fr_FR: {
    "DazeStack Bulk Plugin Manager": "Gestionnaire de Plugins en Masse DazeStack",
    "High-efficiency bulk provisioning for WordPress plugin stacks.": "Approvisionnement en masse haute efficacité pour les piles d'extensions WordPress.",
    "Dashboard": "Tableau de Bord",
    "Bulk Plugin Manager": "Gestionnaire en Masse",
    "About & Legal": "À Propos & Mentions Légales",
    "⭐ Review & Rate Us": "⭐ Avis & Évaluez-nous",
    "Review & Rate Us": "Avis & Évaluez-nous",
    "Recipe Library & Playbooks": "Bibliothèque de Recettes",
    "Save Recipe": "Enregistrer la Recette",
    "Export WP-CLI": "Exporter WP-CLI",
    "Copy to Clipboard": "Copier dans le Presse-papier",
    "Queue Completed": "File d'attente Terminée",
    "Close": "Fermer",
    "Done": "Terminé",
    "Cancel": "Annuler",
    "Installed": "Installé",
    "Live": "En Ligne",
    "Delete": "Supprimer",
    "All clear.": "Tout est clair.",
    "Insufficient permissions.": "Autorisations insuffisantes.",
    "Security alert: critical vulnerability detected.": "Alerte de sécurité : vulnérabilité critique détectée.",
    "Filesystem Credentials Required": "Identifiants du Système de Fichiers Requis"
  },
  hi_IN: {
    "DazeStack Bulk Plugin Manager": "डेज़स्टैक बल्क प्लगइन मैनेजर",
    "High-efficiency bulk provisioning for WordPress plugin stacks.": "वर्डप्रेस प्लगइन स्टैक के लिए उच्च-दक्षता वाला बल्क प्रोविजनिंग टूल।",
    "Dashboard": "डैशबोर्ड",
    "Bulk Plugin Manager": "बल्क प्लगइन मैनेजर",
    "About & Legal": "बारे में और कानूनी",
    "⭐ Review & Rate Us": "⭐ समीक्षा और रेटिंग दें",
    "Review & Rate Us": "समीक्षा और रेटिंग दें",
    "Recipe Library & Playbooks": "रेसिपी लाइब्रेरी और प्लेबुक्स",
    "Save Recipe": "रेसिपी सहेजें",
    "Export WP-CLI": "WP-CLI निर्यात करें",
    "Copy to Clipboard": "क्लिपबोर्ड पर कॉपी करें",
    "Queue Completed": "कतार पूरी हुई",
    "Close": "बंद करें",
    "Done": "संपन्न",
    "Cancel": "रद्द करें",
    "Installed": "स्थापित",
    "Live": "सक्रिय",
    "Delete": "हटाएं",
    "All clear.": "सब साफ़ है।",
    "Insufficient permissions.": "अपर्याप्त अनुमतियाँ।",
    "Security alert: critical vulnerability detected.": "सुरक्षा चेतावनी: गंभीर भेद्यता पाई गई।",
    "Filesystem Credentials Required": "फ़ाइल सिस्टम क्रेडेंशियल्स आवश्यक हैं"
  }
};

for (const [locale, dict] of Object.entries(translations)) {
  const poFile = path.resolve(LANG_DIR, `dazestack-bulk-plugin-manager-${locale}.po`);
  const moFile = path.resolve(LANG_DIR, `dazestack-bulk-plugin-manager-${locale}.mo`);

  let poContent = `msgid ""\nmsgstr ""\n"Project-Id-Version: DazeStack Bulk Plugin Manager 0.0.3\\n"\n"MIME-Version: 1.0\\n"\n"Content-Type: text/plain; charset=UTF-8\\n"\n"Content-Transfer-Encoding: 8bit\\n"\n"Language: ${locale}\\n"\n"X-Domain: dazestack-bulk-plugin-manager\\n"\n\n`;

  for (const [msgid, msgstr] of Object.entries(dict)) {
    poContent += `msgid "${msgid}"\nmsgstr "${msgstr}"\n\n`;
  }

  fs.writeFileSync(poFile, poContent, "utf-8");
  execSync(`msgfmt -o "${moFile}" "${poFile}"`);
  console.log(`✅ Compiled locale ${locale} -> ${path.basename(moFile)}`);
}
