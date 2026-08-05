import { expect, test } from "@playwright/test";

test.describe("Pixel Joy Analytic - E2E flow", () => {
  test("login dan jelajahi dashboard, artikel, proyek, laporan", async ({ page }) => {
    await page.goto("/login");

    await page.getByPlaceholder("admin@hoba.test").fill("admin@hoba.test");
    await page.getByPlaceholder("••••••••").fill("password");
    await page.getByRole("button", { name: "Masuk" }).click();

    await expect(page).toHaveURL(/\/dashboard/);
    await expect(page.getByRole("heading", { name: "Dashboard" })).toBeVisible();
    await expect(page.getByText("Tren Sentimen")).toBeVisible();
    await expect(page.getByText("Total Artikel")).toBeVisible();

    await page.getByRole("link", { name: "Artikel" }).click();
    await expect(page).toHaveURL(/\/articles/);
    await expect(page.getByRole("heading", { name: "Artikel" })).toBeVisible();

    await page.getByRole("link", { name: "Proyek" }).click();
    await expect(page).toHaveURL(/\/projects/);
    await expect(page.getByRole("heading", { name: "Proyek" })).toBeVisible();

    await page.getByRole("link", { name: "Laporan" }).click();
    await expect(page).toHaveURL(/\/reports/);
    await expect(page.getByRole("heading", { name: "Laporan" })).toBeVisible();
  });

  test("login dengan kredensial salah menampilkan error", async ({ page }) => {
    await page.goto("/login");
    await page.getByPlaceholder("admin@hoba.test").fill("admin@hoba.test");
    await page.getByPlaceholder("••••••••").fill("salah-password");
    await page.getByRole("button", { name: "Masuk" }).click();

    await expect(page.getByText("Invalid credentials")).toBeVisible();
  });

  test("halaman login menolak akses halaman terlindungi tanpa token", async ({ page }) => {
    await page.goto("/dashboard");
    await expect(page).toHaveURL(/\/login/);
  });
});
