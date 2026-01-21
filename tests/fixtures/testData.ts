/**
 * Test data generators for WorkTrack E2E tests
 */

/**
 * Generate a unique identifier for test data
 */
export function uniqueId(): string {
  return Date.now().toString(36) + Math.random().toString(36).substring(2, 7);
}

/**
 * Generate test client data
 */
export function generateClient(overrides: Partial<ClientData> = {}): ClientData {
  const id = uniqueId();
  return {
    name: `Test Client ${id}`,
    email: `client-${id}@test.com`,
    phone: `04${Math.floor(Math.random() * 100000000).toString().padStart(8, '0')}`,
    address: `${Math.floor(Math.random() * 999)} Test Street, TestCity`,
    remarks: 'Test client created by Playwright',
    ...overrides,
  };
}

/**
 * Generate test supplier data
 */
export function generateSupplier(overrides: Partial<SupplierData> = {}): SupplierData {
  const id = uniqueId();
  return {
    name: `Test Supplier ${id}`,
    contactName: `Contact ${id}`,
    email: `supplier-${id}@test.com`,
    phone: `02${Math.floor(Math.random() * 100000000).toString().padStart(8, '0')}`,
    address: `${Math.floor(Math.random() * 999)} Supplier Ave, SupplierCity`,
    accountNumber: `ACC-${id}`,
    notes: 'Test supplier created by Playwright',
    ...overrides,
  };
}

/**
 * Generate test material data
 */
export function generateMaterial(overrides: Partial<MaterialData> = {}): MaterialData {
  const id = uniqueId();
  return {
    itemName: `Test Material ${id}`,
    manufacturersCode: `TM-${id}`,
    costExcl: Math.floor(Math.random() * 100) + 10,
    gst: 0, // Will be calculated
    costIncl: 0, // Will be calculated
    sellPrice: Math.floor(Math.random() * 150) + 20,
    stockOnHand: Math.floor(Math.random() * 100),
    reorderQuantity: 10,
    unitOfMeasure: 'each',
    comments: 'Test material created by Playwright',
    ...overrides,
  };
}

/**
 * Generate test project data
 */
export function generateProject(overrides: Partial<ProjectData> = {}): ProjectData {
  const id = uniqueId();
  const startDate = new Date();
  const endDate = new Date();
  endDate.setDate(endDate.getDate() + 14);

  return {
    title: `Test Project ${id}`,
    details: 'Test project created by Playwright for E2E testing',
    startDate: formatDate(startDate),
    completionDate: formatDate(endDate),
    fabric: 'Test Fabric',
    ...overrides,
  };
}

/**
 * Generate test quote data
 */
export function generateQuote(overrides: Partial<QuoteData> = {}): QuoteData {
  const quoteDate = new Date();
  const expiryDate = new Date();
  expiryDate.setDate(expiryDate.getDate() + 30);

  return {
    quoteDate: formatDate(quoteDate),
    expiryDate: formatDate(expiryDate),
    specialInstructions: 'Test quote created by Playwright',
    labourStripping: 30,
    labourPatterns: 15,
    labourCutting: 45,
    labourSewing: 60,
    labourUpholstery: 90,
    labourAssembly: 30,
    labourHandling: 15,
    labourRateType: 'standard' as const,
    ...overrides,
  };
}

/**
 * Format date as YYYY-MM-DD
 */
export function formatDate(date: Date): string {
  return date.toISOString().split('T')[0];
}

// Type definitions
export interface ClientData {
  name: string;
  email: string;
  phone: string;
  address: string;
  remarks: string;
}

export interface SupplierData {
  name: string;
  contactName: string;
  email: string;
  phone: string;
  address: string;
  accountNumber: string;
  notes: string;
}

export interface MaterialData {
  itemName: string;
  manufacturersCode: string;
  costExcl: number;
  gst: number;
  costIncl: number;
  sellPrice: number;
  stockOnHand: number;
  reorderQuantity: number;
  unitOfMeasure: string;
  comments: string;
}

export interface ProjectData {
  title: string;
  details: string;
  startDate: string;
  completionDate: string;
  fabric: string;
}

export interface QuoteData {
  quoteDate: string;
  expiryDate: string;
  specialInstructions: string;
  labourStripping: number;
  labourPatterns: number;
  labourCutting: number;
  labourSewing: number;
  labourUpholstery: number;
  labourAssembly: number;
  labourHandling: number;
  labourRateType: 'standard' | 'premium';
}
