const API_BASE_URL = import.meta.env.VITE_BACKEND_URL || "http://localhost:4000";

export async function fetchArticles() {
  const res = await fetch(`${API_BASE_URL}/api/articles`);

  if (!res.ok) {
    throw new Error(`Failed to fetch articles (status ${res.status})`);
  }

  return res.json();
}

