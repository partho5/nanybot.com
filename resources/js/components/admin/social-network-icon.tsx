import { Globe } from 'lucide-react';

export type SocialNetwork = {
    key: string;
    label: string;
    hosts: string[];
    path: string;
};

/** Network for a URL, matching the host and its subdomains (same rules as App\Support\SocialNetworks). */
export function detectNetwork(
    networks: SocialNetwork[],
    url: string,
): SocialNetwork | null {
    let host: string;

    try {
        host = new URL(url).hostname.toLowerCase().replace(/^www\./, '');
    } catch {
        return null;
    }

    return (
        networks.find((n) =>
            n.hosts.some((h) => host === h || host.endsWith(`.${h}`)),
        ) ?? null
    );
}

export default function SocialNetworkIcon({
    network,
    className = 'size-4',
}: {
    network: SocialNetwork | null;
    className?: string;
}) {
    if (!network) {
        return <Globe className={className} aria-hidden="true" />;
    }

    return (
        <svg
            viewBox="0 0 24 24"
            fill="currentColor"
            className={className}
            aria-hidden="true"
            focusable="false"
        >
            <path d={network.path} />
        </svg>
    );
}
