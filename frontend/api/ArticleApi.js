const API_BASE_URL = process.env.BACKEND_URL

export async function fetchArticles() {
  const res = await fetch(`${API_BASE_URL}/api/articles`);

  if (!res.ok) {
    throw new Error(`Failed to fetch articles (status ${res.status})`);
  }

  return res.json();
}
