/**
 * Format a name by replacing hyphens and underscores with spaces
 * and capitalizing the first letter of each word.
 *
 * Equivalent to PHP: ucwords(str_replace(['-', '_'], ' ', $name))
 *
 * @param name - The name to format
 * @returns Formatted name with capitalized words
 *
 * @example
 * formatName('cron-task') // returns 'Cron Task'
 * formatName('my_topology_name') // returns 'My Topology Name'
 * formatName('cron') // returns 'Cron'
 */
export function formatName(name: string): string {
  return name
    .replace(/[-_]/g, ' ')
    .split(' ')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
    .join(' ')
}
