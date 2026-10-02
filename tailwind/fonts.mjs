// Copies the 6 self-hosted font files (latin subset) into site/assets/fonts/.
import { copyFileSync, mkdirSync } from "node:fs";

const out = "../site/assets/fonts";
const files = [
  "chakra-petch/files/chakra-petch-latin-600-normal.woff2",
  "chakra-petch/files/chakra-petch-latin-700-normal.woff2",
  "ibm-plex-sans/files/ibm-plex-sans-latin-400-normal.woff2",
  "ibm-plex-sans/files/ibm-plex-sans-latin-600-normal.woff2",
  "ibm-plex-sans/files/ibm-plex-sans-latin-500-italic.woff2",
  "ibm-plex-mono/files/ibm-plex-mono-latin-500-normal.woff2",
];

mkdirSync(out, { recursive: true });
for (const f of files) copyFileSync(`node_modules/@fontsource/${f}`, `${out}/${f.split("/").pop()}`);
console.log(`copied ${files.length} fonts to ${out}`);
