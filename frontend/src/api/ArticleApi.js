import { BACKEND_URL } from "../config/config.js";

export async function fetchArticles() {
  const res = await fetch(`${BACKEND_URL}/api/articles`);

  if (!res.ok) {
    throw new Error(`Failed to fetch articles (status ${res.status})`);
  }

  return res.json();
}
